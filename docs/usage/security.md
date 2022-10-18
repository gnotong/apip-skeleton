# App Security

Use the keycloak security bundle to secure your api

[Here the documentation](https://github.com/ubitransports/keycloak-security-bundle.git)

# App test security

To make functional tests on apis you need a token, but the Keycloak security service contacts an external tool called Keycloak to validate the given token.

It is not recommended to call external tools during the tests because of latencies and dependencies(if the external tool is down your tests will not work).

This is why, you need to create a token provider for test. In the feature, that token provider for test will be added to the keycloak security bundle.

But for know, these are the steps:

> Create the TokenHandler.php

```php
<?php
namespace CommentService\Security\Test;
use http\Exception\BadConversionException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;
class TokenHandler
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function decodePayload(string $accessToken): string
    {
        $payload = explode('.', $accessToken)[1];
        $payload = str_replace(['-', '_'], ['+', '/'], $payload);
        $decodedPayload = base64_decode($payload, true);
        if (!is_string($decodedPayload)) {
            throw new BadConversionException('The payload could not be decoded in [decodePayload]');
        }
        return $decodedPayload;
    }

    public function getUserInfo(string $tokenPayload, string $userDto, string $rawAccessToken = null): UserInterface
    {
        $context = [];
        if (null !== $rawAccessToken) {
            $context = [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [UserDto::class => ['accessToken' => $rawAccessToken,],],];
        }
        return $this->serializer->deserialize($tokenPayload, $userDto, JsonEncoder::FORMAT, $context);
    }
}
```

> Create a FakeKeycloakJwtUserProvider.php

This decodes the token and make a user object available for symfony security with all needed roles and metadata

```php
<?php
namespace CommentService\Security\Test;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;
class FakeKeycloakJwtUserProvider implements UserProviderInterface
{
    public function __construct(private readonly TokenHandler $tokenHandler)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userPayload = $this->tokenHandler->decodePayload($identifier);
        return $this->tokenHandler->getUserInfo($userPayload, UserDto::class, $identifier);
    }

    public function supportsClass(string $class): bool
    {
        throw new \RuntimeException(sprintf('Method %s should not be called. Set "stateless" to true in security.yaml file.', __METHOD__));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new \RuntimeException(sprintf('Method %s should not be called. Set "stateless" to true in security.yaml file.', __METHOD__));
    }
}
```

> Make it available for test

In this following file, add this code

```yaml
#config/packages/test/security_test.yaml

security:
    providers:
        keycloak_jwt_user_provider:
            id: CommentService\Security\Test\FakeKeycloakJwtUserProvider
```

With this, all api calls will be checked with this User provider

