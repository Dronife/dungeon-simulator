<?php

namespace App\Http\Controllers;

use App\Models\Simulation\SimAction;
use App\Models\Simulation\SimNpc;
use App\Models\Simulation\SimObject;
use App\Models\Simulation\SimPlace;
use App\Models\Simulation\SimState;
use App\Services\Simulation\Ticker;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SimulationController extends Controller
{
    private const GRID = 60;

    public function __construct(private readonly Ticker $ticker)
    {
    }

    public function index(): Response
    {
        $state = SimState::current();

        $npcs = SimNpc::all();

        $mapNpcs = $npcs->map(fn (SimNpc $n) => [
            'id' => $n->id,
            'name' => $n->name,
            'x' => $n->x,
            'y' => $n->y,
            'current_action' => $n->current_action,
            'archetype' => $n->archetype,
        ])->values();

        $featuredNpcs = $npcs
            ->sortBy(fn (SimNpc $n) => min($n->hunger, $n->thirst, $n->rest, $n->purpose))
            ->take(30)
            ->values();

        $totalForSale = SimObject::where('for_sale', true)->count();
        $totalCoin = (int) $npcs->sum('wealth');

        $stats = [
            'total' => $npcs->count(),
            'avg_hunger' => (int) round($npcs->avg('hunger')),
            'avg_thirst' => (int) round($npcs->avg('thirst')),
            'avg_rest' => (int) round($npcs->avg('rest')),
            'avg_purpose' => (int) round($npcs->avg('purpose')),
            'avg_wealth' => (int) round($npcs->avg('wealth')),
            'total_coin' => $totalCoin,
            'total_for_sale' => $totalForSale,
            'by_mood' => $npcs->groupBy('mood')->map->count()->sortDesc(),
            'by_action' => $npcs->groupBy('current_action')->map->count()->sortDesc(),
            'by_archetype' => $npcs->groupBy('archetype')->map->count()->sortDesc(),
        ];

        return Inertia::render('Simulation/Index', [
            'state' => $state,
            'gridSize' => self::GRID,
            'places' => SimPlace::all(),
            'mapNpcs' => $mapNpcs,
            'featuredNpcs' => $featuredNpcs,
            'objects' => SimObject::whereNull('owner_npc_id')->whereNotNull('x')->get(),
            'recentActions' => SimAction::with('source:id,name')
                ->where('tick', $state->tick)
                ->orderBy('id', 'desc')
                ->limit(30)
                ->get(),
            'stats' => $stats,
        ]);
    }

    public function tick(): RedirectResponse
    {
        $this->ticker->tick();

        return redirect()->route('simulation.index');
    }

    public function reset(): RedirectResponse
    {
        \Artisan::call('migrate:refresh', ['--path' => 'database/migrations/2026_04_11_000000_create_simulation_tables.php']);
        \Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\SimulationSeeder']);

        return redirect()->route('simulation.index');
    }

    public function npc(SimNpc $npc): Response
    {
        return Inertia::render('Simulation/Npc', [
            'npc' => $npc->load('place', 'inventory'),
            'recentActions' => SimAction::where('source_npc_id', $npc->id)
                ->orderBy('id', 'desc')
                ->limit(30)
                ->get(),
        ]);
    }

    public function object(SimObject $object): Response
    {
        return Inertia::render('Simulation/Object', [
            'object' => $object->load('owner', 'place'),
        ]);
    }

    public function place(SimPlace $place): Response
    {
        return Inertia::render('Simulation/Place', [
            'place' => $place->load('npcs', 'objects'),
        ]);
    }
}
