<?php

namespace Eyja\RestBundle\Serializer;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer as JMSSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Serializer {
    /** @var JMSSerializer */
    private $serializer;

    /** @var array */
    private $supportedSerializationTypes = array(
        'application/json' => 'json',
        'application/xml' => 'xml'
    );

    /** @var array */
    private $supportedDeserializationTypes = array(
        'application/json' => 'json',
        'application/xml' => 'xml'
    );

    /**
     * Constructor
     *
     * @param JMSSerializer $serializer
     */
    public function __construct(JMSSerializer $serializer) {
        $this->serializer = $serializer;
    }

    /**
     * Serialize response
     *
     * Serialization type is defined by Accept header.
     *
     * @param Request $request
     * @param Response $response
     * @param mixed $data
     * @param null|string $defaultContentType
     * @throws \Exception
     */
    public function serializeResponse(Request $request, Response $response, $data, $defaultContentType = null) {
        $acceptableTypes = $request->getAcceptableContentTypes();
        $acceptableSupportedTypes = array_intersect($acceptableTypes, array_keys($this->supportedSerializationTypes));
        if (count($acceptableSupportedTypes)>0) {
            $contentType = array_values($acceptableSupportedTypes);
            $contentType = $contentType[0];
            $type = $this->supportedSerializationTypes[$contentType];
        } else if ($defaultContentType !== null) {
            $contentType = $defaultContentType;
            $type = $this->supportedSerializationTypes[$contentType];
        } else {
            throw new \Exception('Unsupported or empty value in Accept header.');
        }
        $groups = $request->attributes->get('serialization_groups', array());
        $content = $this->serializeContent($data, $groups, $type);
        $response->setContent($content);
        $response->headers->set('content-type', $contentType);
    }

    /**
     * Serialized content body
     *
     * @param string $content
     * @param array $groups
     * @param string $type
     * @return string
     */
    public function serializeContent($content, array $groups, $type) {
        $serializationContext = SerializationContext::create();
        $serializationContext->enableMaxDepthChecks();
        $serializationContext->setGroups($groups);
        return $this->serializer->serialize($content, $type, $serializationContext);
    }

    /**
     * Deserialize request
     *
     * Content-Type header defines serialization type
     *
     * @param Request $request
     * @param $objectClass
     * @return mixed
     * @throws \Exception
     */
    public function deserialize(Request $request, $objectClass) {
        $contentType = $request->headers->get('content-type');
        if ($this->isDeserializationTypeSupported($contentType)) {
            $type = $this->supportedDeserializationTypes[$contentType];
            return $this->serializer->deserialize($request->getContent(), $objectClass, $type);
        } else {
            throw new \Exception('Unsupported or empty value in Content-Type header.');
        }
    }

    /**
     * @param string $contentType
     * @return bool
     */
    public function isSerializationTypeSupported($contentType) {
        return array_key_exists($contentType, $this->supportedSerializationTypes);
    }

    /**
     * @param string $contentType
     * @return bool
     */
    public function isDeserializationTypeSupported($contentType) {
        return array_key_exists($contentType, $this->supportedDeserializationTypes);
    }

}