<?php

namespace Fuse\Tools;

use Fuse\Tools\Norm;
use Fuse\Tools\KeyStore;
use JsonSerializable;

use function Fuse\Helpers\Types\{isArray, isBlank};
use function Fuse\Core\config;

class FuseIndex implements JsonSerializable
{
    /**
     * @return static
     */
    public static function create(array $keys, array $docs, array $options = []): self
    {
        // @phpstan-ignore new.static
        $myIndex = new static([
            'getFn' => $options['getFn'] ?? config('getFn'),
            'fieldNormWeight' => $options['fieldNormWeight'] ?? config('fieldNormWeight'),
        ]);
        $myIndex->setKeys(array_map(fn($key) => KeyStore::createKey($key), $keys));
        $myIndex->setSources($docs);
        $myIndex->init();

        return $myIndex;
    }

    /**
     * @return static
     */
    public static function parse(array $data, array $options = []): self
    {
        // @phpstan-ignore new.static
        $myIndex = new static([
            'getFn' => $options['getFn'] ?? config('getFn'),
            'fieldNormWeight' => $options['fieldNormWeight'] ?? config('fieldNormWeight'),
        ]);
        $myIndex->setKeys($data['keys']);
        $myIndex->setIndexRecords($data['records']);

        return $myIndex;
    }

    private bool $isCreated = false;
    private Norm $norm;
    private $getFn;
    private array $docs;
    private array $keysMap;
    public array $records;
    public array $keys;

    public function __construct(array $options = [])
    {
        $fieldNormWeight = $options['fieldNormWeight'] ?? config('fieldNormWeight');
        $this->norm = new Norm($fieldNormWeight, 3);
        $this->getFn = $options['getFn'] ?? config('getFn');

        $this->setIndexRecords();
    }

    public function setSources(array $docs = []): void
    {
        $this->docs = $docs;
    }

    public function setIndexRecords(array $records = []): void
    {
        $this->records = $records;
    }

    public function setKeys(array $keys = []): void
    {
        $this->keys = $keys;
        $this->keysMap = [];
        foreach ($keys as $idx => $key) {
            $this->keysMap[$key['id']] = $idx;
        }
    }

    // Named 'create' in Fuse.js, but we needed the
    // 'create' name for the additional static method
    /**
     * @return void
     */
    public function init()
    {
        if ($this->isCreated || sizeof($this->docs) === 0) {
            return;
        }

        $this->isCreated = true;

        // List is array of strings
        if (is_string($this->docs[0])) {
            foreach ($this->docs as $docIndex => $doc) {
                $this->addString($doc, $docIndex);
            }
        } else {
            // List is array of arrays
            foreach ($this->docs as $docIndex => $doc) {
                $this->addObject($doc, $docIndex);
            }
        }

        $this->norm->clear();
    }

    // Adds a doc to the end of the index
    public function add($doc): void
    {
        $idx = $this->size();

        if (is_string($doc)) {
            $this->addString($doc, $idx);
        } else {
            $this->addObject($doc, $idx);
        }
    }

    // Removes the doc at the specified index of the index
    public function removeAt(int $idx): void
    {
        array_splice($this->records, $idx, 1);

        // Change ref index of every subsquent doc
        for ($i = $idx, $len = $this->size(); $i < $len; $i += 1) {
            $this->records[$i]['i'] -= 1;
        }
    }

    public function getValueForItemAtKeyId(array $item, $keyId)
    {
        return $item[$this->keysMap[$keyId]];
    }

    public function size(): int
    {
        return sizeof($this->records);
    }

    /**
     * @return void
     */
    private function addString(?string $doc, int $docIndex)
    {
        if (is_null($doc) || isBlank($doc)) {
            return;
        }

        $record = [
            'v' => $doc,
            'i' => $docIndex,
            'n' => $this->norm->get($doc),
        ];

        $this->records[] = $record;
    }

    private function addObject($doc, int $docIndex): void
    {
        $record = [
            'i' => $docIndex,
            '$' => [],
        ];

        // Iterate over every key (i.e, path), and fetch the value at that key
        foreach ($this->keys as $keyIndex => $key) {
            if ($key['getFn'] ?? false ?: false) {
                $value = call_user_func($key['getFn'], $doc);
            } else {
                $value = call_user_func($this->getFn, $doc, $key['path']);
            }

            if (is_null($value)) {
                continue;
            }

            if (isArray($value)) {
                $subRecords = [];
                $stack = [['nestedArrIndex' => -1, 'value' => $value]];

                while (sizeof($stack) > 0) {
                    $stackItem = array_pop($stack);
                    $value = $stackItem['value'];

                    if (is_null($value)) {
                        continue;
                    }

                    if (is_string($value) && !isBlank($value)) {
                        $subRecord = [
                            'v' => $value,
                            'i' => $stackItem['nestedArrIndex'],
                            'n' => $this->norm->get($value),
                        ];

                        $subRecords[] = $subRecord;
                    } elseif (isArray($value)) {
                        foreach ($value as $k => $item) {
                            $stack[] = [
                                'nestedArrIndex' => $k,
                                'value' => $item,
                            ];
                        }
                    } else {
                        // If we're here, the `path` is either incorrect, or pointing to a non-string.
                        // throw new \Exception(sprintf('Path "%s" points to a non-string value. Received: %s', $key, $value))
                    }
                }

                $record['$'][$keyIndex] = $subRecords;
            } elseif (is_string($value) && !isBlank($value)) {
                $subRecord = [
                    'v' => $value,
                    'n' => $this->norm->get($value),
                ];

                $record['$'][$keyIndex] = $subRecord;
            }
        }

        $this->records[] = $record;
    }

    public function jsonSerialize(): array
    {
        return [
            'keys' => $this->keys,
            'records' => $this->records,
        ];
    }
}
