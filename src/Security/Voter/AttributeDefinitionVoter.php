<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\AttributeDefinition;

final class AttributeDefinitionVoter extends Voter
{
    public const DELETE = 'ATTRIBUTE_DELETE';
    public const EDIT = 'ATTRIBUTE_EDIT';
    public const BATCH_DELETE = 'ATTRIBUTE_BATCH_DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $adm
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::DELETE, self::EDIT], true)) {
            return $subject instanceof AttributeDefinition;
        }
        if ($attribute === self::BATCH_DELETE) {
            return is_array($subject) &&  $subject !== [] && array_all($subject, fn($ad) => $ad instanceof AttributeDefinition);
        }
        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');
            return false;
        }
        if (!$this->adm->decide($token, ['ROLE_RECRUITER'])) {
            $vote?->addReason('The user does not have permission to manage attributes.');
            return false;
        }
        return match ($attribute) {
            self::DELETE => $this->canDelete($subject, $vote),
            self::EDIT => true,
            self::BATCH_DELETE => $this->canBatchDelete($subject, $vote),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canDelete(AttributeDefinition $attributeDefinition, ?Vote $vote = null): bool
    {
        if ($attributeDefinition->isBuiltIn()) {
            $vote?->addReason('Built-in attributes cannot be deleted.');
            return false;
        }
        return true;
    }

    private function canBatchDelete(array $attributeDefinitions, ?Vote $vote = null): bool
    {
        if (array_any($attributeDefinitions, fn($ad) => !$this->canDelete($ad, $vote))) {
            return false;
        }
        return true;
    }
}
