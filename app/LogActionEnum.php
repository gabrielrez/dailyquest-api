<?php

namespace App;

enum LogActionEnum: string
{
    case USER_REGISTERED = 'user_registered';
    case USER_LOGGED_IN  = 'user_logged_in';
    case USER_LOGGED_OUT = 'user_logged_out';
    case USER_PROFILE_UPDATED = 'user_profile_updated';
    case USER_PROFILE_PICTURE_UPDATED = 'user_profile_picture_updated';

    case COLLECTION_CREATED = 'collection_created';
    case COLLECTION_DELETED = 'collection_deleted';
    case COLLECTION_COMPLETED = 'collection_completed';
    case COLLECTION_JOINED = 'collection_joined';

    case GOAL_COMPLETED = 'goal_completed';

    case INVITATION_ACCEPTED = 'invitation_accepted';
    case INVITATION_SENT = 'invitation_sent';
}
