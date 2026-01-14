<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpecResource;
use App\Models\Spec;
use App\Services\SpecGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpecController extends Controller
{
    public function __construct(protected SpecGeneratorService $specGenerator) {}

    /**
     * Display a listing of specs.
     */
    public function index(): Response
    {
        $specs = Spec::with('project')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Specs/Index', [
            'specs' => SpecResource::collection($specs),
        ]);
    }

    /**
     * Show the form for creating a new spec.
     */
    public function create(): Response
    {
        return Inertia::render('Specs/Create');
    }

    /**
     * Generate a spec from an idea using AI.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idea' => 'required|string|min:10|max:5000',
            'type' => 'required|in:feature,api,refactor,bug',
        ]);

        try {
            $content = $this->specGenerator->generate(
                $validated['idea'],
                $validated['type']
            );

            return response()->json([
                'success' => true,
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created spec.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $spec = Spec::create($validated);

        return redirect()->route('specs.show', $spec)->with('success', 'Spec created successfully.');
    }

    /**
     * Display the specified spec.
     */
    public function show(Spec $spec): Response
    {
        $spec->load('project');

        return Inertia::render('Specs/Show', [
            'spec' => new SpecResource($spec),
        ]);
    }

    /**
     * Show the form for editing the specified spec.
     */
    public function edit(Spec $spec): Response
    {
        return Inertia::render('Specs/Edit', [
            'spec' => new SpecResource($spec),
        ]);
    }

    /**
     * Update the specified spec.
     */
    public function update(Request $request, Spec $spec): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $spec->update($validated);

        return redirect()->route('specs.show', $spec)->with('success', 'Spec updated successfully.');
    }

    /**
     * Remove the specified spec.
     */
    public function destroy(Spec $spec): \Illuminate\Http\RedirectResponse
    {
        $spec->delete();

        return redirect()->route('specs.index')->with('success', 'Spec deleted successfully.');
    }

    /**
     * Refine an existing spec with feedback.
     */
    public function refine(Request $request, Spec $spec): JsonResponse
    {
        $validated = $request->validate([
            'feedback' => 'required|string|min:10|max:2000',
        ]);

        try {
            $refinedContent = $this->specGenerator->refine(
                $spec->content,
                $validated['feedback']
            );

            return response()->json([
                'success' => true,
                'content' => $refinedContent,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
