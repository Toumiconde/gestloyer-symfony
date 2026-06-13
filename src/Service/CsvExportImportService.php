<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service handling CSV export and import for Doctrine entities.
 *
 * - Export: Returns a CSV string with column headers based on entity getters.
 * - Import: Parses an uploaded CSV, creates new records and updates existing ones.
 */
class CsvExportImportService
{
    /**
     * Export all records of a given entity class to CSV.
     */
    public function export(EntityManagerInterface $em, string $entityClass): string
    {
        $repo = $em->getRepository($entityClass);
        $objects = $repo->findAll();
        if (empty($objects)) {
            return '';
        }

        // Reflect entity properties via getter methods (public non-static)
        $reflection = new \ReflectionClass($entityClass);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $headers = [];
        $getterMap = [];
        foreach ($methods as $m) {
            $name = $m->getName();
            if (strpos($name, 'get') === 0 && $m->getNumberOfParameters() === 0) {
                $prop = lcfirst(substr($name, 3));
                $headers[] = $prop;
                $getterMap[$prop] = $name;
            }
        }

        $csv = Writer::createFromString('');
        $csv->insertOne($headers);
        foreach ($objects as $obj) {
            $row = [];
            foreach ($getterMap as $prop => $getter) {
                $value = $obj->$getter();
                // Simple scalar conversion; dates are formatted, enums cast to string
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('c');
                } elseif (is_object($value) && method_exists($value, '__toString')) {
                    $value = (string) $value;
                }
                $row[] = $value;
            }
            $csv->insertOne($row);
        }
        return $csv->getContent();
    }

    /**
     * Import CSV data into the given entity.
     * Returns an array [createdCount, updatedCount].
     */
    public function import(EntityManagerInterface $em, string $entityClass, UploadedFile $file): array
    {
        $reader = Reader::createFromPath($file->getRealPath(), 'r');
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        $repo = $em->getRepository($entityClass);
        $meta = $em->getClassMetadata($entityClass);
        $identifier = $meta->getIdentifierFieldNames(); // usually ['id']
        $identifierField = $identifier[0] ?? null;

        $created = 0;
        $updated = 0;
        $em->getConnection()->beginTransaction();
        try {
            foreach ($records as $record) {
                // Determine if row exists (by primary key)
                $entity = null;
                if ($identifierField && isset($record[$identifierField])) {
                    $entity = $repo->find($record[$identifierField]);
                }
                if (!$entity) {
                    $entity = new $entityClass();
                    $created++;
                } else {
                    $updated++;
                }
                // Set values via setters (setX)
                foreach ($record as $field => $value) {
                    $setter = 'set' . ucfirst($field);
                    if ($meta->hasField($field) && method_exists($entity, $setter)) {
                        // Basic type casting – can be extended for dates/enums
                        $type = $meta->getTypeOfField($field);
                        if ($type === 'datetime' && $value !== '') {
                            $value = new \DateTime($value);
                        }
                        $entity->$setter($value);
                    }
                }
                $em->persist($entity);
            }
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();
            throw $e;
        }
        return [$created, $updated];
    }
}
