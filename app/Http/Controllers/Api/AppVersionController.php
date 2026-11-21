<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * Release check for the mobile app.
     *
     * The app sends its platform, installed version and build number; the
     * published values in config/app_update.php decide whether an update is
     * offered (`update_available`) or enforced (`update_required`). The check
     * fails open: unparseable or unknown input never blocks the app.
     */
    public function show(Request $request)
    {
        $platform = strtolower(trim((string) $request->query('platform', '')));
        $currentVersion = trim((string) $request->query('version', ''));
        $currentBuild = (int) $request->query('build', 0);

        $release = config("app_update.$platform");
        if (!is_array($release)) {
            $release = config('app_update.android', []);
        }

        $latestVersion = trim((string) ($release['version'] ?? ''));
        $latestBuild = (int) ($release['build'] ?? 0);
        $storeUrl = trim((string) ($release['store_url'] ?? ''));

        $updateAvailable = $currentVersion !== '' && (
            $this->isVersionOlder($currentVersion, $latestVersion)
            || (
                $this->isSameVersion($currentVersion, $latestVersion)
                && $latestBuild > 0
                && $currentBuild > 0
                && $currentBuild < $latestBuild
            )
        );

        $updateRequired = $updateAvailable
            && filter_var(config('app_update.required'), FILTER_VALIDATE_BOOLEAN);

        return response()->json([
            'update_available' => $updateAvailable,
            'update_required' => $updateRequired,
            'latest_version' => $latestVersion,
            'latest_build' => $latestBuild,
            'store_url' => $storeUrl,
            'store_link' => $storeUrl,
            'message' => $this->message($updateRequired),
            'remind_later_hours' => (int) config('app_update.remind_later_hours', 24),
        ]);
    }

    private function message(bool $updateRequired): string
    {
        $message = trim((string) config('app_update.message', ''));
        if ($message !== '') {
            return $message;
        }

        return $updateRequired
            ? 'Please update Tully Smiths to continue using the app.'
            : 'A new version of Tully Smiths is available with the latest improvements and fixes.';
    }

    private function isSameVersion(string $current, string $target): bool
    {
        if ($current === '' || $target === '') {
            return false;
        }

        return !$this->isVersionOlder($current, $target)
            && !$this->isVersionOlder($target, $current);
    }

    private function isVersionOlder(string $current, string $target): bool
    {
        if ($target === '' || $current === '') {
            return false;
        }

        $currentSegments = $this->versionSegments($current);
        $targetSegments = $this->versionSegments($target);
        $length = max(count($currentSegments), count($targetSegments));

        for ($i = 0; $i < $length; $i++) {
            $currentValue = $currentSegments[$i] ?? 0;
            $targetValue = $targetSegments[$i] ?? 0;

            if ($currentValue !== $targetValue) {
                return $currentValue < $targetValue;
            }
        }

        return false;
    }

    /**
     * @return array<int, int>
     */
    private function versionSegments(string $version): array
    {
        return array_map(
            static fn (string $segment): int => (int) preg_replace('/\D+/', '', $segment),
            explode('.', $version)
        );
    }
}
