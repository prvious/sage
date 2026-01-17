<?php

namespace App\Actions\Server;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

final readonly class TestServerConnection
{
    /**
     * Test if the server is accessible for the project.
     *
     * @return array{success: bool, message: string}
     */
    public function handle(Project $project): array
    {
        try {
            $url = $project->base_url;

            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Server is accessible and responding.',
                ];
            }

            return [
                'success' => false,
                'message' => "Server returned status code {$response->status()}.",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Connection failed: {$e->getMessage()}",
            ];
        }
    }
}
