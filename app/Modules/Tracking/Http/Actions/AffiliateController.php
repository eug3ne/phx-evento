<?php

declare(strict_types=1);

namespace App\Modules\Tracking\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Affiliate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AffiliateController
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function index(int $eventId, Request $request): JsonResponse
    {
        $query = Affiliate::where('event_id', $eventId);

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->query('status')));
        }
        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $affiliates = $query->orderByDesc('total_sales')->orderByDesc('id')->get();

        return response()->json(['data' => $affiliates]);
    }

    public function store(int $eventId, Request $request): JsonResponse
    {
        $input = $request->all();
        if (isset($input['code'])) {
            $input['code'] = strtoupper(trim((string) $input['code']));
        }

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,INACTIVE'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        $exists = Affiliate::where('event_id', $eventId)
            ->whereRaw('UPPER(code) = ?', [$input['code']])
            ->exists();

        if ($exists) {
            return $this->serializer->validationErrorResponse([
                'code' => ['The affiliate code has already been taken for this event.'],
            ], 422);
        }

        $user = $request->user('api') ?? Auth::guard('api')->user();

        $affiliate = Affiliate::create([
            'event_id' => $eventId,
            'user_id' => $user?->getAuthIdentifier(),
            'name' => (string) $input['name'],
            'code' => (string) $input['code'],
            'email' => $input['email'] ?? null,
            'status' => (string) ($input['status'] ?? 'ACTIVE'),
            'total_sales' => 0,
            'total_sales_gross' => 0.00,
        ]);

        return response()->json(['data' => $affiliate], 201);
    }

    public function update(int $eventId, int $affiliateId, Request $request): JsonResponse
    {
        $affiliate = Affiliate::where('event_id', $eventId)->where('id', $affiliateId)->first();
        if (! $affiliate) {
            return $this->serializer->errorResponse('Not Found', 'Affiliate not found.', 404);
        }

        $input = $request->all();
        if (isset($input['code'])) {
            $input['code'] = strtoupper(trim((string) $input['code']));
        }

        $validator = Validator::make($input, [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,INACTIVE'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        if (isset($input['code'])) {
            $duplicate = Affiliate::where('event_id', $eventId)
                ->where('id', '!=', $affiliateId)
                ->whereRaw('UPPER(code) = ?', [$input['code']])
                ->exists();

            if ($duplicate) {
                return $this->serializer->validationErrorResponse([
                    'code' => ['The affiliate code has already been taken for this event.'],
                ], 422);
            }
        }

        $affiliate->fill(array_intersect_key($input, array_flip(['name', 'code', 'email', 'status'])));
        $affiliate->save();

        return response()->json(['data' => $affiliate]);
    }

    public function destroy(int $eventId, int $affiliateId): JsonResponse
    {
        $affiliate = Affiliate::where('event_id', $eventId)->where('id', $affiliateId)->first();
        if (! $affiliate) {
            return $this->serializer->errorResponse('Not Found', 'Affiliate not found.', 404);
        }

        $affiliate->delete();

        return response()->json(null, 204);
    }

    public function export(int $eventId): Response
    {
        $affiliates = Affiliate::where('event_id', $eventId)->orderByDesc('total_sales')->get();

        $lines = ['id,name,code,email,status,total_sales,total_sales_gross,created_at'];
        foreach ($affiliates as $affiliate) {
            $lines[] = implode(',', [
                $affiliate->id,
                '"'.str_replace('"', '""', (string) $affiliate->name).'"',
                $affiliate->code,
                $affiliate->email ?? '',
                $affiliate->status,
                $affiliate->total_sales,
                number_format((float) $affiliate->total_sales_gross, 2, '.', ''),
                optional($affiliate->created_at)->format('Y-m-d H:i:s'),
            ]);
        }

        $csv = implode("\n", $lines)."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"event-{$eventId}-affiliates.csv\"",
        ]);
    }
}
