<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:generate:entities',
    description: 'Automatically generates entity classes from the database schema',
)]
class GenerateEntitiesCommand extends Command
{
    private Connection $connection;
    private ?AbstractSchemaManager $schemaManager = null;
    private array $generatedRelations = [];

    public function __construct(Connection $connection, Filesystem $filesystem)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Generating Entity Classes from Database...");

        try {
            $this->registerEnumType();
            $schemaManager = $this->getSchemaManager();
            $tables = $schemaManager->listTables();
        } catch (\Exception $e) {
            $io->error("Failed to retrieve database schema: " . $e->getMessage());
            return Command::FAILURE;
        }

        $oneToManyRelations = [];
        $manyToOneRelationsName = [];
        $oneToManyRelationsName = [];

        // Count relations for each table
        $tableRelationsCount = [];
        foreach ($tables as $table) {
            $foreignKeys = $this->getForeignKeys([$table->getName()]);
            $relationCount = count($foreignKeys);
            $tableRelationsCount[$table->getName()] = $relationCount;
        }

        // Sort tables by their relation count in ascending order
        usort($tables, function (Table $a, Table $b) use ($tableRelationsCount) {
            return $tableRelationsCount[$a->getName()] <=> $tableRelationsCount[$b->getName()];
        });

        // Generate entities in sorted order
        foreach ($tables as $table) {
            $this->generateEntity($table, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);
            $io->success("Generated: src/Entity/" . ucfirst($table->getName()) . ".php");
        }

        foreach ($tables as $table) {
            $this->generateEntity($table, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);
            $io->success("Relations Added: src/Entity/" . ucfirst($table->getName()) . ".php");
        }

        $io->success("Entities successfully generated in src/Entity/");
        return Command::SUCCESS;
    }

    private function registerEnumType(): void
    {
        if (!$this->connection->getDatabasePlatform()->hasDoctrineTypeMappingFor('enum')) {
            $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        }
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        if ($this->schemaManager === null) {
            $this->schemaManager = $this->connection->createSchemaManager();
        }
        return $this->schemaManager;
    }

    private function generateEntity(Table $table, array &$oneToManyRelations, array &$manyToOneRelationsName, array &$oneToManyRelationsName): void
    {
        $className = ucfirst($table->getName());
        $entityCode = "<?php\n\nnamespace App\\Entity;\n\nuse Doctrine\\ORM\\Mapping as ORM;\n\n";

        $imports = $this->generateImports($manyToOneRelationsName, $oneToManyRelationsName, $className);
        $entityCode .= $imports . "\n";

        $entityCode .= "#[ORM\\Entity]\n";
        $entityCode .= "class $className\n{\n";

        // Initialize collections for OneToMany relations
        $entityCode .= "    public function __construct()\n    {\n";
        if (isset($oneToManyRelations[$className])) {
            foreach ($oneToManyRelations[$className] as $relation) {
                $relationArray = $this->parseRelationAnnotation($relation);
                $propertyName = strtolower($relationArray['targetEntity']) . 's';
                $entityCode .= "        \$this->$propertyName = new \Doctrine\Common\Collections\ArrayCollection();\n";
            }
        }
        $entityCode .= "    }\n\n";

        $primaryKeys = $table->getPrimaryKey()?->getColumns() ?? [];
        $foreignKeys = $this->getForeignKeys([$table->getName()]);

        foreach ($table->getColumns() as $column) {
            $entityCode .= $this->generateProperty($column, $primaryKeys, $foreignKeys, $className, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);
        }

        foreach ($table->getColumns() as $column) {
            $entityCode .= $this->generateGettersAndSetters($column);
        }

        if (isset($oneToManyRelations[$className])) {
            $processedRelations = [];
            foreach ($oneToManyRelations[$className] as $relation) {
                if (!in_array($relation, $processedRelations)) {
                    $entityCode .= $relation;
                    $processedRelations[] = $relation;

                    $relationArray = $this->parseRelationAnnotation($relation);
                    $relationKey = "$className-{$relationArray['mappedBy']}";

                    if (!isset($this->generatedRelations[$relationKey])) {
                        $entityCode .= $this->generateRelationMethods($className, $relationArray['mappedBy'], $relationArray['targetEntity']);
                        $this->generatedRelations[$relationKey] = true;
                    }
                }
            }
        }

        $entityCode .= "}\n";
        file_put_contents(__DIR__ . "/../../src/Entity/$className.php", $entityCode);
    }

    private function generateImports(array $manyToOneRelationsName, array $oneToManyRelationsName, string $className): string
    {
        $imports = [];

        foreach ($manyToOneRelationsName as $key => $value) {
            if ($key === $className) {
                $imports[] = "App\\Entity\\$value";
            }
        }

        foreach ($oneToManyRelationsName as $key => $value) {
            if ($key === $className) {
                $imports[] = "Doctrine\Common\Collections\Collection";
                $imports[] = "Doctrine\Common\Collections\ArrayCollection";
                $imports[] = "App\\Entity\\$value";
            }
        }

        $imports = array_unique($imports);
        return count($imports) ? "use " . implode(";\nuse ", $imports) . ";\n" : "";
    }

    public function getForeignKeys(array $tables): array
    {
        $foreignKeys = [];
        $schemaManager = $this->connection->createSchemaManager();
        $dbTables = $schemaManager->listTables();

        foreach ($tables as $tableName) {
            if (in_array($tableName, array_map(fn($table) => $table->getName(), $dbTables))) {
                $sql = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_NAME = :tableName AND REFERENCED_TABLE_NAME IS NOT NULL";
                
                $stmt = $this->connection->prepare($sql);
                $stmt->bindValue(':tableName', $tableName);
                $fks = $stmt->executeQuery()->fetchAllAssociative();

                foreach ($fks as $fk) {
                    $foreignKeys[$fk['COLUMN_NAME']] = [
                        'referencedTable' => $fk['REFERENCED_TABLE_NAME'],
                        'referencedColumn' => $fk['REFERENCED_COLUMN_NAME']
                    ];
                }
            }
        }

        return $foreignKeys;
    }

    private function generateRelationMethods(string $currentEntity, string $propertyName, string $relatedEntity): string
    {
        $collectionType = "Collection";
        $relatedEntityClass = ucfirst($relatedEntity);
        $relatedEntityVariable = lcfirst($relatedEntity);

        return "
    public function get{$relatedEntityClass}s(): $collectionType
    {
        return \$this->{$relatedEntityVariable}s;
    }

    public function add{$relatedEntityClass}({$relatedEntityClass} \${$relatedEntityVariable}): self
    {
        if (!\$this->{$relatedEntityVariable}s->contains(\${$relatedEntityVariable})) {
            \$this->{$relatedEntityVariable}s[] = \${$relatedEntityVariable};
            \${$relatedEntityVariable}->set" . ucfirst($propertyName) . "(\$this);
        }
        return \$this;
    }

    public function remove{$relatedEntityClass}({$relatedEntityClass} \${$relatedEntityVariable}): self
    {
        if (\$this->{$relatedEntityVariable}s->removeElement(\${$relatedEntityVariable})) {
            if (\${$relatedEntityVariable}->get" . ucfirst($propertyName) . "() === \$this) {
                \${$relatedEntityVariable}->set" . ucfirst($propertyName) . "(null);
            }
        }
        return \$this;
    }\n";
    }

    private function generateProperty(Column $column, array $primaryKeys, array $foreignKeys, string $className, array &$oneToManyRelations, array &$manyToOneRelationsName, array &$oneToManyRelationsName): string
    {
        $columnName = $column->getName();
        $type = $column->getType()->getName();
        $length = $column->getLength();
        $isPrimaryKey = in_array($columnName, $primaryKeys);
        $isForeignKey = isset($foreignKeys[$columnName]);

        $doctrineType = match ($type) {
            'integer', 'int' => 'integer',
            'bigint' => 'bigint',
            'smallint' => 'smallint',
            'boolean', 'bool' => 'boolean',
            'datetime', 'timestamp' => 'datetime',
            'date' => 'date',
            'text' => 'text',
            'decimal', 'float', 'double' => 'float',
            'string', 'varchar', 'enum' => 'string',
            default => 'string',
        };

        $lengthAnnotation = ($doctrineType === 'string' && $length) ? ", length: $length" : "";
        $propertyCode = "\n    " . ($isPrimaryKey ? "#[ORM\\Id]\n    " : "");

        if ($isForeignKey) {
            $relatedEntity = $foreignKeys[$columnName]['referencedTable'];
            $relatedClassName = ucfirst($relatedEntity);
            $primaryKeyColumns = $this->getPrimaryKeyColumns($relatedEntity);
            $primaryKeyColumn = $primaryKeyColumns ? $primaryKeyColumns[0] : null;

            if ($primaryKeyColumn) {
                $propertyCode .= "    #[ORM\\ManyToOne(targetEntity: $relatedClassName::class, inversedBy: \"" . strtolower($className) . "s\")]\n";
                $propertyCode .= "    #[ORM\\JoinColumn(name: '$columnName', referencedColumnName: '$primaryKeyColumn', onDelete: 'CASCADE')]\n";
                $propertyCode .= "    private $relatedClassName \$$columnName;\n";

                $manyToOneRelationsName[$className] = $relatedClassName;
                $oneToManyRelationsName[$relatedClassName] = $className;
                $oneToManyRelations[$relatedClassName][] = "\n    #[ORM\\OneToMany(mappedBy: \"$columnName\", targetEntity: $className::class)]\n    private Collection \$" . strtolower($className) . "s;\n";
            }
        } else {
            $propertyCode .= "#[ORM\\Column(type: \"$doctrineType\"$lengthAnnotation)]\n";
            $propertyCode .= "    private " . $this->getPHPTypeFromDoctrine($doctrineType) . " \$$columnName;\n";
        }

        return $propertyCode;
    }

    private function getPHPTypeFromDoctrine(string $doctrineType): string
    {
        $mapping = [
            'integer' => 'int',
            'smallint' => 'int',
            'bigint' => 'string',
            'string' => 'string',
            'text' => 'string',
            'boolean' => 'bool',
            'decimal' => 'string',
            'float' => 'float',
            'date' => '\DateTimeInterface',
            'datetime' => '\DateTimeInterface',
            'datetimetz' => '\DateTimeInterface',
            'time' => '\DateTimeInterface',
            'array' => 'array',
            'json' => 'array',
            'object' => 'object',
            'binary' => 'string',
            'blob' => 'string',
            'guid' => 'string',
        ];
        return $mapping[$doctrineType] ?? 'mixed';
    }

    private function getPrimaryKeyColumns(string $tableName): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes($tableName);
        return isset($indexes['primary']) ? $indexes['primary']->getColumns() : [];
    }

    private function generateGettersAndSetters(Column $column): string
    {
        $columnName = $column->getName();
        $methodName = ucfirst($columnName);
        $type = $this->getPHPTypeFromDoctrine($column->getType()->getName());

        return "
    public function get$methodName(): $type
    {
        return \$this->$columnName;
    }

    public function set$methodName($type \$value): self
    {
        \$this->$columnName = \$value;
        return \$this;
    }\n";
    }

    private function parseRelationAnnotation(string $relation): array
    {
        $pattern = '/mappedBy:\s*"([^"]+)",\s*targetEntity:\s*([^\s:]+)::class/';
        preg_match($pattern, $relation, $matches);
        return [
            'mappedBy' => $matches[1] ?? null,
            'targetEntity' => $matches[2] ?? null
        ];
    }
}