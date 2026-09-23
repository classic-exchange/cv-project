<?php

namespace App\Controller\Admin;

use App\Entity\AttributeCategory;
use App\Entity\AttributeDefinition;
use App\Enum\AttributeDataType;
use App\Security\Voter\AttributeDefinitionVoter;
use App\Repository\AttributeDefinitionRepository;
use App\Form\AttributeOptionType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AttributeDefinitionCrudController extends AbstractCrudController
{
    private const DELETE_SELECTED_ACTION = 'deleteSelectedAttributes';

    public static function getEntityFqcn(): string
    {
        return AttributeDefinition::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextareaField::new('description');
        yield AssociationField::new('category')
            ->setFormTypeOption('choice_label', 'name')
            ->formatValue(static fn(AttributeCategory $category): string => $category->getName());
        yield ChoiceField::new('dataType')
            ->setFormTypeOption('choice_label', 'label')
            ->setChoices(AttributeDataType::cases())
            ->setHtmlAttribute('data-attribute-options-target', 'dataType')
            ->setHtmlAttribute('data-action', 'change->attribute-options#updateOptions');
        yield CollectionField::new('options')
            ->setEntryType(AttributeOptionType::class)
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('row_attr.data-attribute-options-target', 'options')
            ->onlyOnForms();
        yield BooleanField::new('builtIn')
            ->renderAsSwitch(false)
            ->onlyOnIndex();
        yield IntegerField::new('version')
            ->addCssClass('d-none')
            ->setTemplatePath('admin/attribute_definition/batch_metadata.html.twig')
            ->onlyOnIndex();
    }

    public function configureActions(Actions $actions): Actions
    {
        $deleteSelected = Action::new(self::DELETE_SELECTED_ACTION, 'Delete', 'fa fa-trash')
            ->linkToCrudAction(self::DELETE_SELECTED_ACTION)
            ->setHtmlAttributes([
                'data-attribute-actions-target' => 'deleteButton'
            ]);
        return $actions
            ->setPermission(Action::NEW, 'ROLE_RECRUITER')
            ->setPermission(Action::DELETE, AttributeDefinitionVoter::DELETE)
            ->setPermission(Action::EDIT, AttributeDefinitionVoter::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->addBatchAction($deleteSelected);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(10)
            ->setFormOptions([
                'attr' => [
                    'class' => 'attribute-options-loading',
                    'data-controller' => 'attribute-options',
                    'data-attribute-options-one-of-many-type-value' => AttributeDataType::ONE_OF_MANY->value,
                    'data-attribute-options-max-options-value' => AttributeDefinition::ONE_OF_MANY_OPTIONS_LIMIT
                ]
            ])
            ->overrideTemplate('crud/index', 'admin/attribute_definition/index.html.twig');
    }

    #[AdminRoute('/delete-selected-attributes', 'delete_selected_attributes', options: ['methods' => ['POST']])]
    public function deleteSelectedAttributes(BatchActionDto $dto, AttributeDefinitionRepository $repo, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('ea-batch-action-' . $dto->getName() . '-' . $dto->getEntityFqcn(), $dto->getCsrfToken())) {
            throw $this->createAccessDeniedException();
        }
        if (static::getEntityFqcn() !== $dto->getEntityFqcn()) {
            throw $this->createAccessDeniedException();
        }
        $ids = array_values(array_unique($dto->getEntityIds()));
        if ($ids === []) {
            throw new \InvalidArgumentException('No attributes selected.');
        }
        $expectedVersions = $this->normalizeExpectedVersions($ids, $request);
        if ($expectedVersions === null) {
            $this->addFlash('danger', 'Invalid attribute version data.');
            return $this->redirectToRoute('admin_attribute_definition_index');
        }
        $attributeDefinitions = $repo->findByIds($ids);
        if (count($ids) !== count($attributeDefinitions)) {
            $this->addFlash('danger', 'One or more selected attributes no longer exist.');
            return $this->redirectToRoute('admin_attribute_definition_index');
        }
        if (!$this->isGranted(AttributeDefinitionVoter::BATCH_DELETE, $attributeDefinitions)) {
            $this->addFlash('danger', 'Built-in attributes cannot be deleted.');
            return $this->redirectToRoute('admin_attribute_definition_index');
        }
        return $this->deleteSelectedAttributesIfUnchanged($attributeDefinitions, $em, $expectedVersions);
    }

    private function normalizeExpectedVersions(array $ids, Request $request): ?array
    {
        $rawVersions = $request->request->all('expectedVersions');
        $expectedVersions = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (!array_key_exists($id, $rawVersions)) {
                return null;
            }
            $version = $rawVersions[$id];
            if (!is_scalar($version) || !ctype_digit((string) $version)) {
                return null;
            }
            $expectedVersions[$id] = (int) $version;
        }
        if (count($expectedVersions) !== count($rawVersions)) {
            return null;
        }
        return $expectedVersions;
    }

    private function deleteSelectedAttributesIfUnchanged(array $attributeDefinitions, EntityManagerInterface $em, array $expectedVersions): RedirectResponse
    {
        try {
            foreach ($attributeDefinitions as $ad) {
                $em->lock($ad, LockMode::OPTIMISTIC, $expectedVersions[$ad->getId()]);
            }
            foreach ($attributeDefinitions as $ad) {
                $em->remove($ad);
            }
            $em->flush();
            $this->addFlash('success', sprintf('%d attribute(s) deleted.', count($attributeDefinitions)));
        } catch (OptimisticLockException $e) {
            $this->addFlash('danger', 'Selected attributes were changed by someone else.');
        }
        return $this->redirectToRoute('admin_attribute_definition_index');
    }
}
