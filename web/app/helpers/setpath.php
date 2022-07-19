<?php

function setPath($slug = null): array|string|null
{
    return preg_replace('!/+!', '/', sprintf(
        "/%s/%s/%s",
        env('APP_API_PREFIX', ''),
        env('APP_API_VERSION', ''),
        $slug
    ));
}
