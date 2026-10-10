<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * المسار النسبي يُطلب على النطاق الرئيسي دائمًا، لا على مضيف آخر طلبٍ قبله
     * (مولّد الروابط يأخذ جذره من الطلب الأخير). اختبارات مضيف الوزارة تقلبه
     * بـ Tests\Concerns\OnGovHost.
     */
    protected function prepareUrlForRequest($uri)
    {
        if (is_string($uri) && ! preg_match('#^https?://#', $uri)) {
            $uri = $this->mainUrl($uri);
        }

        return parent::prepareUrlForRequest($uri);
    }

    /**
     * عنوان مطلق على مضيف بوابات الوزارة (config/hawat.php → gov_domain).
     */
    protected function govUrl(string $path = '/'): string
    {
        return 'http://'.config('hawat.gov_domain').'/'.ltrim($path, '/');
    }

    /**
     * عنوان مطلق على النطاق الرئيسي (config/hawat.php → domain).
     */
    protected function mainUrl(string $path = '/'): string
    {
        return 'http://'.config('hawat.domain').'/'.ltrim($path, '/');
    }
}
