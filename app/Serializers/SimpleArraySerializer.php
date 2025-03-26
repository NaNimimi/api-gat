<?php

namespace App\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class SimpleArraySerializer extends ArraySerializer
{
    public function collection(?string $resourceKey, array $data): array
    {
        return $data;
    }
}
