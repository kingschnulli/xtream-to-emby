<?php

namespace App\Service\Xtream;

use App\Service\Xtream\Struct\SafeStreamName;

class StreamNameSanitizer
{
    public function sanitize(string $name): SafeStreamName
    {
        $safeName = trim(preg_replace('/[^\p{L}\p{M}\w\- ]/mu', '', $name));
        $safeDir = preg_replace('/[^A-Z0-9]/m', '#', strtoupper(substr($safeName,0,1)));
        return new SafeStreamName($safeName, $safeDir);
    }
}