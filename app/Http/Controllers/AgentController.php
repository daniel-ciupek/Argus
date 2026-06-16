<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        $agents = [];
        foreach ($user->agents()->orderBy('name')->get() as $agent) {
            $agents[] = [
                'id' => $agent->id,
                'name' => $agent->name,
                'slug' => $agent->slug,
                'is_active' => $agent->is_active,
                'last_seen_at' => $agent->last_seen_at?->toIso8601String(),
                'created_at' => $agent->created_at->toIso8601String(),
            ];
        }

        return Inertia::render('Agents', [
            'agents' => $agents,
            'newSecret' => session('newSecret'),
            'newSecretAgentId' => session('newSecretAgentId'),
        ]);
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $this->authorize('create', Agent::class);

        $user = $request->user();
        assert($user !== null);

        $name = $request->string('name')->toString();
        $plain = Str::random(40);

        $agent = $user->agents()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'ingest_secret' => $plain,
            'is_active' => true,
        ]);

        return redirect()->route('agents')
            ->with('newSecret', $plain)
            ->with('newSecretAgentId', $agent->id);
    }

    public function update(UpdateAgentRequest $request, Agent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);

        $agent->update([
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active', $agent->is_active),
        ]);

        return redirect()->route('agents');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $this->authorize('delete', $agent);
        $agent->delete();

        return redirect()->route('agents');
    }

    public function rotateSecret(Agent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);

        $plain = Str::random(40);
        $agent->update(['ingest_secret' => $plain]);

        return redirect()->route('agents')
            ->with('newSecret', $plain)
            ->with('newSecretAgentId', $agent->id);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base.'-'.Str::lower(Str::random(6));

        while (Agent::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }
}
