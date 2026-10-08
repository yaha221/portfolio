<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Factory\PageViewFactory;
use App\Message\PageViewed;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\TestWith;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[ResetDatabase]
final class PageViewApiTest extends ApiTestCase
{
    use InteractsWithMessenger;

    private const string BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) Firefox/140.0';

    protected function setUp(): void
    {
        parent::setUp();
        self::getContainer()->get('cache.rate_limiter')->clear();
    }

    public function testRecordsViewWithoutStoringTheIp(): void
    {
        $this->view('/projects/portfolio', self::BROWSER);
        self::assertResponseStatusCodeSame(202);

        $queued = $this->transport('async')->queue()->messages(PageViewed::class);
        self::assertCount(1, $queued);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $queued[0]->visitorHash);

        $this->transport('async')->process();

        $view = PageViewFactory::repository()->findOneBy([]);
        self::assertNotNull($view);
        self::assertSame('/projects/portfolio', $view->getPath());
        self::assertStringNotContainsString('127.0.0.1', $view->getIpHash());
    }

    #[TestWith(['Googlebot/2.1 (+http://www.google.com/bot.html)'])]
    #[TestWith(['Mozilla/5.0 (compatible) HeadlessChrome/140.0'])]
    #[TestWith([''])]
    public function testIgnoresBotsAndRequestsWithoutUserAgent(string $userAgent): void
    {
        $this->view('/', $userAgent);

        self::assertResponseStatusCodeSame(202);
        $this->transport('async')->queue()->assertEmpty();
    }

    #[TestWith(['projects'])]
    #[TestWith(['/projects?utm=1'])]
    #[TestWith(['https://example.com/'])]
    public function testRejectsAnythingButAPath(string $path): void
    {
        $this->view($path, self::BROWSER);

        self::assertSame(['path'], $this->assertViolations());
    }

    public function testAnalyticsCountsViewsAndVisitorsPerDay(): void
    {
        $today = new DateTimeImmutable('today 10:00');
        $yesterday = $today->modify('-1 day');
        PageViewFactory::createMany(3, ['path' => '/', 'ipHash' => 'a', 'createdAt' => $today]);
        PageViewFactory::createOne(['path' => '/projects', 'ipHash' => 'b', 'createdAt' => $today]);
        PageViewFactory::createOne(['path' => '/projects', 'ipHash' => 'c', 'createdAt' => $today->modify('-2 days')]);
        PageViewFactory::createOne(['path' => '/old', 'ipHash' => 'd', 'createdAt' => $today->modify('-10 days')]);
        $this->loginAsAdmin();

        $data = $this->getJson('/api/admin/analytics?days=3');

        self::assertSame($today->modify('-2 days')->format('Y-m-d'), $data['from']);
        self::assertSame([
            ['date' => $today->modify('-2 days')->format('Y-m-d'), 'views' => 1, 'visitors' => 1],
            ['date' => $yesterday->format('Y-m-d'), 'views' => 0, 'visitors' => 0],
            ['date' => $today->format('Y-m-d'), 'views' => 4, 'visitors' => 2],
        ], $data['days']);
        self::assertSame(5, $data['totalViews']);
        self::assertSame(3, $data['totalVisitors']);
        self::assertSame([['path' => '/', 'views' => 3], ['path' => '/projects', 'views' => 2]], $data['topPaths']);
    }

    private function view(string $path, string $userAgent): void
    {
        $this->client->request('POST', '/api/page-views', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_USER_AGENT' => $userAgent,
        ], content: json_encode(['path' => $path], \JSON_THROW_ON_ERROR));
    }
}
