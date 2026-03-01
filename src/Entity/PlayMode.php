<?php

namespace App\Entity;

enum PlayMode: string
{
    case ONLINE = 'En Ligne';
    case ONSITE = 'Sur Site';
}
