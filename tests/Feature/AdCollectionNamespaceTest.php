<?php

namespace Tests\Feature;

use App\Http\Resources\Ad\AdCollection;
use App\Http\Resources\Ad\AdResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use ReflectionClass;
use Tests\TestCase;

/**
 * Guards the PSR-4 namespace of AdCollection. The file app/Http/Resources/Ad/AdCollection.php once
 * declared `namespace App\Http\Resources\Support` while living in Ad/, so it was PSR-4 non-compliant
 * — composer skipped it from the optimized classmap and it did not autoload under any name. It must
 * declare `namespace App\Http\Resources\Ad` (matching its location and its sibling AdResource).
 */
class AdCollectionNamespaceTest extends TestCase
{
    public function test_ad_collection_autoloads_under_its_psr4_namespace(): void
    {
        // class_exists triggers PSR-4 autoloading; false here means the file's namespace doesn't
        // match its path (the exact bug this guards).
        $this->assertTrue(
            class_exists(AdCollection::class),
            'AdCollection must autoload under App\\Http\\Resources\\Ad (namespace must match its path)'
        );
        $this->assertTrue(is_subclass_of(AdCollection::class, ResourceCollection::class));

        // And it still collects AdResource (also resolved within the Ad namespace).
        $this->assertSame(
            AdResource::class,
            (new ReflectionClass(AdCollection::class))->getDefaultProperties()['collects']
        );
    }
}
