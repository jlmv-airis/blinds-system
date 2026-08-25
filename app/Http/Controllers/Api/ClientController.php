<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Throwable;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $clients,
        ], 200);
    }

    public function store(StoreClientRequest $request)
    {
        try {
            $client = Client::create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $client,
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el cliente',
            ], 500);
        }
    }

    public function show(Client $client)
    {
        return response()->json([
            'success' => true,
            'data' => $client,
        ], 200);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        try {
            $client->update($request->validated());

            return response()->json([
                'success' => true,
                'data' => $client->fresh(),
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el cliente',
            ], 500);
        }
    }

    public function destroy(Client $client)
    {
        try {
            // soft delete via is_active, no se borra el registro físicamente
            $client->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente desactivado',
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el cliente',
            ], 500);
        }
    }
}
