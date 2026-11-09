<?php

namespace App\Exports;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps a model to an export row from a [heading => attribute] list so headings
 * always line up with values, regardless of table column order or appended attributes.
 * Attributes may be dotted paths into loaded relations (e.g. "state.name_en").
 */
trait MapsAttributeColumns
{
    /**
     * @return array<string, string>
     */
    abstract protected function columns(): array;

    public function headings(): array
    {
        return array_keys($this->columns());
    }

    public function map(mixed $row): array
    {
        return array_map(
            fn (string $attribute) => $this->exportValue($row, $attribute),
            array_values($this->columns())
        );
    }

    private function exportValue(Model $row, string $attribute): mixed
    {
        $value = data_get($row, $attribute);

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return $value;
    }
}
