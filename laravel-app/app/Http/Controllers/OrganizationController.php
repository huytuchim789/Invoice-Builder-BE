<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isNull;

class OrganizationController extends Controller
{

    protected $uploadPreset;

    public function __constructor()
    {
        $this->uploadPreset = "ftcwbla2";
    }

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreOrganizationRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrganizationRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user(); // Retrieve the currently authenticated user
            $organization = $user->organization; // Assuming a user has an "organization" relationship

            if ($organization) {
                $organization->update($validatedData); // Update the organization with the validated data
                if ($validatedData['logo'] && empty($validatedData['logo_url'])) {
                    $organization->updateMedia($validatedData['logo'], ['upload_presets' => $this->uploadPreset]);
                }
                if (isNull($validatedData['logo'])) {
                    $organization->detachMedia();
                }
            } else {
                $organization = Organization::create($validatedData); // Create a new organization if it doesn't exist for the user
                if ($validatedData['logo'] && empty($validatedData['logo_url'])) {
                    $organization->attachMedia($validatedData['logo'], ['upload_presets' => $this->uploadPreset]);
                }
                if (isNull($validatedData['logo'])) {
                    $organization->detachMedia();
                }
                $user->organization()->associate($organization); // Associate the organization with the user
                $user->save();
            }

            // Return a response indicating success
            return Response::customJson(200, array_merge($validatedData, ["logo_url" => $organization->fetchFirstMedia(), "check" => $validatedData['logo']]), ['message' => 'Organization updated successfully']);
        } catch (\Exception $e) {
            // Return a response with an error message
            return Response::customJson(500, null, ['message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Organization $organization
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    public function getSettings()
    {
        try {
            $user = User::findOrFail(Auth::user()->id);
            $organization = $user->organization;

            if (!$organization) {
                return Response::customJson(404, null, ['message' => 'Organization not found for the specified user ID']);
            }

            // You can access the organization properties directly or format the response as needed
            $organizationDetail = [
                'id' => $organization->id,
                'logo_url' => $organization->fetchFirstMedia()->file_url ?? null,
                'name' => $organization->name,
                'address' => $organization->address,
                'phone' => $organization->phone,
                'email' => $organization->email,
                'created_at' => $organization->created_at,
                'updated_at' => $organization->updated_at,
            ];

            // Return the organization detail as a custom JSON response
            return Response::customJson(200, $organizationDetail, ['message' => 'Organization details retrieved successfully']);
        } catch (\Exception $e) {
            // Return a response with an error message
            return Response::customJson(500, null, ['message' => $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Organization $organization
     * @return \Illuminate\Http\Response
     */
    public function edit(Organization $organization)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateOrganizationRequest $request
     * @param \App\Models\Organization $organization
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Organization $organization
     * @return \Illuminate\Http\Response
     */
    public function destroy(Organization $organization)
    {
        //
    }
}
