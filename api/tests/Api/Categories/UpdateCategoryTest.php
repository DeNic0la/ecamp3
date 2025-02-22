<?php

namespace App\Tests\Api\Categories;

use App\Tests\Api\ECampApiTestCase;

/**
 * @internal
 */
class UpdateCategoryTest extends ECampApiTestCase {
    public function testPatchCategoryIsDeniedForAnonymousUser() {
        $category = static::getFixture('category1');
        static::createBasicClient()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            'preferredContentTypes' => [
                $this->getIriFor('contentTypeColumnLayout'),
                $this->getIriFor('contentTypeSafetyConcept'),
            ],
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPatchCategoryIsDeniedForUnrelatedUser() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials(['email' => static::$fixtures['user4unrelated']->getEmail()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testPatchCategoryIsDeniedForInactiveCollaborator() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials(['email' => static::$fixtures['user5inactive']->getEmail()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testPatchCategoryIsDeniedForGuest() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials(['email' => static::$fixtures['user3guest']->getEmail()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Access Denied.',
        ]);
    }

    public function testPatchCategoryIsAllowedForMember() {
        $category = static::getFixture('category1');
        $response = static::createClientWithCredentials(['email' => static::$fixtures['user2member']->getEmail()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            '_links' => [
                'preferredContentTypes' => [
                    'href' => '/content_types?categories='.urlencode($this->getIriFor($category)),
                ],
            ],
        ]);
    }

    public function testPatchCategoryIsAllowedForManager() {
        $category = static::getFixture('category1');
        $response = static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            'preferredContentTypes' => [
                $this->getIriFor('contentTypeColumnLayout'),
                $this->getIriFor('contentTypeSafetyConcept'),
            ],
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            '_links' => [
                'preferredContentTypes' => [
                    'href' => '/content_types?categories='.urlencode($this->getIriFor($category)),
                ],
            ],
        ]);
    }

    public function testPatchCategoryInCampPrototypeIsDeniedForUnrelatedUser() {
        $category = static::getFixture('category1campPrototype');
        $response = static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            'preferredContentTypes' => [
                $this->getIriFor('contentTypeColumnLayout'),
                $this->getIriFor('contentTypeSafetyConcept'),
            ],
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Access Denied.',
        ]);
    }

    public function testPatchCategoryDisallowsChangingCamp() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'camp' => $this->getIriFor('camp2'),
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'detail' => 'Extra attributes are not allowed ("camp" is unknown).',
        ]);
    }

    public function testPatchCategoryIsAllowsEmptyPreferredContentTypes() {
        $category = static::getFixture('category1');
        $client = static::createClientWithCredentials();
        $client->disableReboot();
        $client->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'preferredContentTypes' => [],
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'], ]
        );
        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'GET',
            '/content_types?categories='.urlencode($this->getIriFor($category))
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                'totalItems' => 0,
            ]
        );
    }

    public function testPatchCategoryValidatesNullShort() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'short' => null,
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'The type of the "short" attribute must be "string", "NULL" given.',
        ]);
    }

    public function testPatchCategoryValidatesBlankShort() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'short' => ' ',
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'short',
                    'message' => 'This value should not be blank.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryValidatesTooLongShort() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'short' => str_repeat('l', 17),
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'short',
                    'message' => 'This value is too long. It should have 16 characters or less.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryTrimsShort() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'short' => "  \t LS\t ",
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'], ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                'short' => 'LS',
            ]
        );
    }

    public function testPatchCategoryCleansForbiddenCharactersFromShort() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'short' => "L<b>S</b>\n\t<a>",
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'], ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                'short' => 'L<b>S</b><a>',
            ]
        );
    }

    public function testPatchCategoryValidatesNullName() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'name' => null,
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'The type of the "name" attribute must be "string", "NULL" given.',
        ]);
    }

    public function testPatchCategoryValidatesBlankName() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'name' => ' ',
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'name',
                    'message' => 'This value should not be blank.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryValidatesTooLongName() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'name' => str_repeat('l', 33),
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'name',
                    'message' => 'This value is too long. It should have 32 characters or less.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryTrimsName() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'name' => "  \t Lagersport\t ",
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'], ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                'name' => 'Lagersport',
            ]
        );
    }

    public function testPatchCategoryCleansForbiddenCharactersFromName() {
        $category = static::getFixture('category1');
        $client = static::createClientWithCredentials();
        $client->disableReboot();
        $client->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'name' => "<b>Lager</b>sport\n\t<a>",
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'], ]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                'name' => '<b>Lager</b>sport<a>',
            ]
        );
    }

    public function testPatchCategoryValidatesNullColor() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'color' => null,
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'detail' => 'The type of the "color" attribute must be "string", "NULL" given.',
        ]);
    }

    public function testPatchCategoryValidatesEmptyColor() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'color' => '',
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'color',
                    'message' => 'This value should not be blank.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryValidatesInvalidColor() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'color' => 'green',
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'color',
                    'message' => 'This value is not valid.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryTrimsColor() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request(
            'PATCH',
            '/categories/'.$category->getId(),
            [
                'json' => [
                    'color' => " \t #4DBB52 \t ",
                ],
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'color' => '#4DBB52',
        ]);
    }

    public function testPatchCategoryValidatesInvalidNumberingStyle() {
        $category = static::getFixture('category1');
        static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'numberingStyle' => 'X',
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'numberingStyle',
                    'message' => 'The value you selected is not a valid choice.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryPurgesCacheTags() {
        $client = static::createClientWithCredentials();
        $cacheManager = $this->mockCacheManager();

        $category = static::getFixture('category1');
        $client->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'short' => 'LP',
            'preferredContentTypes' => [
                $this->getIriFor('contentTypeColumnLayout'),
                $this->getIriFor('contentTypeNotes'),
            ],
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);

        $this->assertResponseStatusCodeSame(200);

        $contentTypeColumnLayout = static::getFixture('contentTypeColumnLayout');
        $contentTypeNotes = static::getFixture('contentTypeNotes');
        $contentTypeSafetyConcept = static::getFixture('contentTypeSafetyConcept');
        self::assertEqualsCanonicalizing([
            $category->getId(),
            $contentTypeColumnLayout->getId().'#categories',
            $contentTypeNotes->getId().'#categories',
            $contentTypeSafetyConcept->getId().'#categories', // SafetyConcept was previously in the list, so this is purged because it was removed
        ], $cacheManager->getInvalidatedTags());
    }
}
