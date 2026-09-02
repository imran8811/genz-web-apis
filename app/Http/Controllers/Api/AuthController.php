<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        return response()->json([
            'message' => 'Account created successfully!',
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        return response()->json([
            'message' => 'Login successful!',
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Delete the signed-in customer's account (Google Play requires this to be
     * available in-app for any app that lets you create an account).
     *
     * The user row is anonymised rather than deleted: orders reference it, and
     * destroying them would erase the sales history the business runs on. What
     * actually goes is every piece of personal data — the profile, the saved
     * addresses, the cart, and the name/phone/address copied onto each past
     * order — leaving only the figures and timestamps needed for the books.
     *
     * Deletion is refused while an order is still in flight, because the
     * kitchen and the rider still need the delivery details to complete it.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['That password is incorrect.'],
            ]);
        }

        $active = $user->orders()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->exists();

        if ($active) {
            return response()->json([
                'message' => 'You have an order on the way. We can delete your account once it has been delivered or cancelled.',
            ], 409);
        }

        DB::transaction(function () use ($user): void {
            // Strip the customer's details from past orders, keeping the money
            // and the line items so reporting still adds up.
            $user->orders()->update([
                'shipping_name' => 'Deleted user',
                'shipping_phone' => '',
                'shipping_address_line_1' => 'Deleted',
                'shipping_address_line_2' => null,
                'shipping_area' => null,
                'shipping_landmark' => null,
                'notes' => null,
            ]);

            $user->shippingAddresses()->delete();
            $user->cart?->delete();
            $user->tokens()->delete();

            $user->forceFill([
                'name' => 'Deleted user',
                // Unguessable and unroutable, so the account can never be
                // logged into or recovered, and the unique index still holds.
                'email' => 'deleted-' . $user->id . '-' . Str::random(16) . '@deleted.invalid',
                'phone' => null,
                'password' => Hash::make(Str::random(64)),
            ])->save();
        });

        return response()->json(['message' => 'Your account and personal data have been deleted.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $data['email']],
            ['token' => Hash::make($token), 'created_at' => now()],
        );

        $payload = ['message' => 'If that email exists, a reset link has been sent.'];
        // In local/dev we surface the token so the flow is testable without mail.
        if (config('app.debug')) {
            $payload['reset_token'] = $token;
        }

        return response()->json($payload);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record || ! Hash::check($data['token'], $record->token)) {
            throw ValidationException::withMessages(['token' => ['Invalid or expired reset token.']]);
        }
        if (now()->diffInMinutes($record->created_at) > 60) {
            throw ValidationException::withMessages(['token' => ['This reset token has expired.']]);
        }

        User::where('email', $data['email'])->update(['password' => Hash::make($data['password'])]);
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Password reset successful! You can now log in.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
        ];
    }
}
