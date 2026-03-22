<?php

namespace Combindma\Strapi\Http\Controllers;

use Combindma\Strapi\Facades\Strapi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WebhookController extends Controller
{
    /**
     * @throws RuntimeException
     */
    public function webhookHandler(Request $request): JsonResponse
    {
        if (! $this->isValid($request)) {
            Log::error('Invalid Strapi webhook request.', [
                'payload' => $request->all(),
            ]);

            return response()->json('Invalid webhook request.', 400);
        }

        $this->clearCache($request);

        return response()->json('Cache cleared');
    }

    /**
     * @throws RuntimeException
     */
    protected function isValid(Request $request): bool
    {
        $signature = $request->header('Signature');

        if ($signature === null || $signature === '') {
            Log::error('Missing Strapi webhook signature.', [
                'payload' => $request->all(),
            ]);

            return false;
        }

        $signingSecret = (string) config('strapi.webhook_secret', '');

        if ($signingSecret === '') {
            throw new RuntimeException('The Strapi webhook secret is not set. Make sure that the `webhook_secret` config key is configured.');
        }

        return hash_equals($signature, $signingSecret);
    }

    protected function clearCache(Request $request): void
    {
        $model = (string) $request->input('model', '');
        $documentId = $request->input('entry.documentId');
        $slug = $request->input('entry.slug');

        Strapi::clearModel(
            $model,
            $documentId,
            $slug,
        );
    }
}
