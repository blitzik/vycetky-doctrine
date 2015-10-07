<?php

namespace Exceptions\Runtime;

    class RuntimeException extends \RuntimeException {}

        /**
         * Entity that just does NOT have an ID
         * It does not have anything to do with Doctrine's Entity Persistent State
         *
         * Class NotPersistedEntityInstanceException
         * @package Exceptions\Runtime
         */

        class InvalidMemberTypeException extends RuntimeException {}

        // Users
        class DuplicateEntryException extends RuntimeException {}
    
            class DuplicateEmailException extends DuplicateEntryException {}
    
            class DuplicateUsernameException extends DuplicateEntryException {}
            
        class DatabaseUserInsertException extends RuntimeException {}

        class UserNotFoundException extends RuntimeException {}

        class UserAlreadyExistsException extends RuntimeException {}

        class InvitationValidityException extends RuntimeException {}

            class InvitationNotFoundException extends InvitationValidityException {}

            class InvitationAlreadyExistsException extends InvitationValidityException {}

            class InvitationExpiredException extends InvitationValidityException {}

            class InvitationTokenMatchException extends InvitationValidityException {}

        class InvalidUserInvitationEmailException extends RuntimeException {}

        class InvitationCreationAttemptException extends RuntimeException{}

        class RestrictedUserException extends RuntimeException {}

        class InaccessibleAccountException extends RuntimeException {}


        class InvalidStateException extends RuntimeException {}

        // Listings
        class ListingNotFoundException extends RuntimeException {}
        
        class ListingItemNotFoundException extends RuntimeException {}

        class ListingItemDayAlreadyExistsException extends RuntimeException {}

        class LocalityNotFoundException extends RuntimeException {}
        
        class WorkedHoursNotFoundException extends RuntimeException {}
        
        class DuplicateItemInCollectionException extends RuntimeException {}

        class NegativeResultOfTimeCalcException extends RuntimeException {}

		class InvalidTimeMemberTypeException extends RuntimeException {}

        class DayExceedException extends RuntimeException {}

        class ShiftItemUpException extends RuntimeException {}

        class ShiftItemDownException extends RuntimeException {}

        class ListingAlreadyContainsListingItemException extends RuntimeException {}

        class ShiftEndBeforeStartException extends RuntimeException {}

        class ListingPreviewNotFoundException extends RuntimeException {}

        class CollisionItemsSelectionException extends RuntimeException {}

        class NoCollisionListingItemSelectedException extends RuntimeException {}

        // Messages
        class MessageLengthException extends RuntimeException {}

        class MessageNotFoundException extends RuntimeException {}

        class MessageTypeException extends RuntimeException {}

        // WorkedHours
        class OtherHoursZeroTimeException extends RuntimeException {}