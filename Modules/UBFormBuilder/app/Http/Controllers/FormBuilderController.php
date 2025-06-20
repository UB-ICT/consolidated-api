<?php

namespace Modules\UBFormBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\UBFormBuilder\Models\Form;

class FormBuilderController extends Controller
{
    protected $form;

    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    // Create a new form
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.required' => 'boolean',
            // Add more validation rules as needed
        ]);

        $formId = $this->form->create($validated);

        return response()->json([
            'success' => true,
            'form_id' => $formId,
            'message' => 'Form created successfully'
        ], 201);
    }

    // Get all forms
    public function index()
    {
        $forms = $this->form->all();
        return response()->json(['forms' => $forms]);
    }

    // Get a specific form
    public function show($id)
    {
        $form = $this->form->find($id);

        if (!$form) {
            return response()->json(['error' => 'Form not found'], 404);
        }

        return response()->json(['form' => $form]);
    }

    // Update a form
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'sometimes|array',
            'fields.*.type' => 'sometimes|string',
            'fields.*.label' => 'sometimes|string',
            'fields.*.required' => 'boolean',
        ]);

        $this->form->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Form updated successfully'
        ]);
    }

    // Delete a form
    public function destroy($id)
    {
        $this->form->delete($id);
        return response()->json(['success' => true, 'message' => 'Form deleted']);
    }

    // Get form schema for builder UI
    public function getBuilderSchema($id)
    {
        $form = $this->form->find($id);

        if (!$form) {
            return response()->json(['error' => 'Form not found'], 404);
        }

        // Transform the data for the form builder UI
        $schema = [
            'title' => $form['title'] ?? '',
            'description' => $form['description'] ?? '',
            'fields' => $form['fields'] ?? []
        ];

        return response()->json(['schema' => $schema]);
    }

    // Save form structure from builder
    public function saveBuilderSchema(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array', // For select, radio, etc.
        ]);

        $this->form->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Form schema saved successfully'
        ]);
    }

    public function submitForm(Request $request, $formId)
    {
        $form = $this->form->find($formId);

        if (!$form) {
            return response()->json(['error' => 'Form not found'], 404);
        }

        // Validate against form fields
        $validationRules = [];
        foreach ($form['fields'] as $field) {
            if ($field['required'] ?? false) {
                $validationRules[$field['name'] ?? $field['label']] = 'required';
            }
        }

        $validated = $request->validate($validationRules);

        $submissionId = $this->form->submitForm($formId, $validated);

        return response()->json([
            'success' => true,
            'submission_id' => $submissionId,
            'message' => 'Form submitted successfully'
        ]);
    }

    public function getSubmissions($formId)
    {
        $submissions = $this->form->getSubmissions($formId);
        return response()->json(['submissions' => $submissions]);
    }
}
