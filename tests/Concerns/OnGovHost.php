<?php

namespace Tests\Concerns;

/**
 * لاختبارات بوابات الوزارة: المسار النسبي يُطلب على مضيفها (gov_domain) لا على
 * النطاق الرئيسي. والعنوان المطلق — كناتج route() — يذهب إلى مضيفه كما هو.
 */
trait OnGovHost
{
    protected function prepareUrlForRequest($uri)
    {
        if (is_string($uri) && ! preg_match('#^https?://#', $uri)) {
            $uri = $this->govUrl($uri);
        }

        return parent::prepareUrlForRequest($uri);
    }
}
