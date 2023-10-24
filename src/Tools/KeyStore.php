<?php

namespace Fuse\Tools;

use Fuse\Exception\InvalidKeyWeightValueException;
use Fuse\Exception\MissingKeyPropertyException;
use JsonSerializable;

use function Fuse\Helpers\Types\isArray;

class KeyStore implements JsonSerializable
{
    public static function createKeyPath($key): array
    {
        return isArray($key) ? $key : explode('.', $key);
    }

    public static function createKeyId($key): string
    {
        return isArray($key) ? join('.', $key) : $key;
    }

    public static function createKey($key): array
    {
        $weight = 1;
        $getFn = null;

        if (is_string($key) || isArray($key)) {
            $src = $key;
            $path = static::createKeyPath($key);
            $id = static::createKeyId($key);
        } else {
            if (!isset($key['name'])) {
                throw new MissingKeyPropertyException('name');
            }

            $name = $key['name'];
            $src = $name;

            if (isset($key['weight'])) {
                $weight = $key['weight'];

                if ($weight <= 0) {
                    throw new InvalidKeyWeightValueException($name);
                }
            }

            $path = static::createKeyPath($name);
            $id = static::createKeyId($name);
            $getFn = $key['getFn'] ?? null;
        }

        return [
            'path' => $path,
            'id' => $id,
            'weight' => $weight,
            'src' => $src,
            'getFn' => $getFn,
        ];
    }

    private array $keys = [];
    private array $keyMap = [];

    public function __construct(array $keys)
    {
        $totalWeight = 0;

        foreach ($keys as $key) {
            // Need to unset to destroy the reference
            unset($obj);

            $obj = static::createKey($key);

            $this->keys[] = &$obj;
            $this->keyMap[$obj['id']] = &$obj;

            $totalWeight += $obj['weight'];
        }

        // Normalize weights so that their sum is equal to 1
        foreach ($this->keys as &$key) {
            $key['weight'] /= $totalWeight;
        }
    }

    public function get(string $keyId)
    {
        return $this->keyMap[$keyId];
    }

    public function keys(): array
    {
        return $this->keys;
    }

    public function jsonSerialize(): array
    {
        return $this->keys;
    }
}
