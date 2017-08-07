<?php
namespace Microsoft\Rest\Internal\Types;

use Microsoft\Rest\Internal\Data\DataAbstract;
use Microsoft\Rest\Internal\InvalidSchemaObjectException;
use Microsoft\Rest\Internal\Types\Primitives\ObjectType;
use Microsoft\Rest\Internal\Types\Primitives\PrimitiveTypeAbstract;

abstract class TypeAbstract
{
    /**
     * @param mixed $value
     * @return string
     */
    abstract function toJson($value);

    /**
     * @return bool
     */
    abstract function isConst();

    /**
     * @return string
     */
    abstract function getConstValue();

    /**
     * @param TypeAbstract[] $typeMap
     * @return TypeAbstract
     */
    abstract function removeRefTypes(array $typeMap);

    /**
     * @param DataAbstract $schemaObjectData see https://swagger.io/specification/#schemaObject
     * @return TypeAbstract
     * @throws \Exception
     */
    protected static function createFromDataWithRefs(DataAbstract $schemaObjectData)
    {
        // https://swagger.io/specification/#data-types-12
        /**
         * @var string
         */
        $ref = $schemaObjectData->getChildValueOrNull('$ref');
        if ($ref !== null) {
            return new RefType($ref, $schemaObjectData);
        }

        /**
         * @var string
         */
        $type = $schemaObjectData->getChildValueOrNull('type');
        if ($type !== null) {
            switch ($type) {
                case 'array':
                    return ArrayType::createFromDataWithRefs($schemaObjectData);
                case 'object':
                    $additionalPropertiesData = $schemaObjectData->getChildOrNull('additionalProperties');
                    return $additionalPropertiesData === null
                        ? new ObjectType()
                        : MapType::createFromItemData($additionalPropertiesData);
                default:
                    return PrimitiveTypeAbstract::createFromDataWithRefs($schemaObjectData);
            }
        }

        // ClassType
        $properties = $schemaObjectData->getChildOrNull('properties');
        if ($properties !== null) {
            return ClassType::createFromDataWithRefs($properties);
        }

        throw new InvalidSchemaObjectException($schemaObjectData);
    }

    /**
     * @param TypeAbstract[] $typeMap
     * @param DataAbstract $schemaObjectData
     * @return TypeAbstract
     */
    static function createFromData(array $typeMap, DataAbstract $schemaObjectData)
    {
        return self::createFromDataWithRefs($schemaObjectData)->removeRefTypes($typeMap);
    }

    /**
     * @param DataAbstract $schemaObjectMapData
     * @param string $prefix
     * @return TypeAbstract[]
     */
    static function createMapFromData(DataAbstract $schemaObjectMapData, $prefix = '')
    {
        /**
         * @var TypeAbstract[]
         */
        $typeMap = [];
        foreach ($schemaObjectMapData->getChildren() as $child)
        {
            $typeMap[$prefix . $child->getKey()] = TypeAbstract::createFromDataWithRefs($child);
        }
        return $typeMap;
    }

    /**
     * @param TypeAbstract[] $definitions
     * @param TypeAbstract[] $typeMap
     * @return TypeAbstract[]
     */
    static function removeRefTypesFromMap(array $definitions, array $typeMap)
    {
        /**
         * @var TypeAbstract[]
         */
        $result = [];
        foreach ($typeMap as $name => $value) {
            $result[$name] = $value->removeRefTypes($definitions);
        }
        return $result;
    }
}