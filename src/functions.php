<?php

use Interakt\Track\Track;

function interakt_track_user(?string $userId = null, string $countryCode = '+91', ?string $phoneNumber = null, array $traits = [])
{
    return Track::user($userId, $countryCode, $phoneNumber, $traits);
}

function interakt_track_event(?string $userId = null, ?string $event = null, array $traits = [], string $countryCode = '+91', ?string $phoneNumber = null)
{
    return Track::event($userId, $event, $traits, $countryCode, $phoneNumber);
}

function interakt_track_flush(): void
{
    Track::flush();
}

function interakt_track_shutdown(): void
{
    Track::shutdown();
}
