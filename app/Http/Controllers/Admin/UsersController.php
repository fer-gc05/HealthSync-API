<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\StorePatientRequest;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Requests\Admin\UpdatePatientRequest;
use App\Http\Requests\Admin\UpdateDoctorRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UsersController extends Controller
{
    /**
     * Listar usuarios
     */
    public function index(IndexUserRequest $request)
    {
        $query = User::with(['patient', 'medicalStaff', 'roles']);
        
        // Búsqueda por nombre o email
        if ($request->validated('q')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('email', 'like', '%' . $request->q . '%');
            });
        }
        
        // Filtro por rol
        if ($request->validated('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        // Manejo de soft deletes
        if ($request->validated('with_trashed')) {
            $query->withTrashed();
        }
        if ($request->validated('only_trashed')) {
            $query->onlyTrashed();
        }
        
        // Ordenamiento
        $sortBy = $request->validated('sort_by') ?? 'created_at';
        $sortDir = $request->validated('sort_dir') ?? 'desc';
        $query->orderBy($sortBy, $sortDir);
        
        // Paginación
        $perPage = $request->validated('per_page') ?? 15;
        $users = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $users
        ], Response::HTTP_OK);
    }


    /**
     * Mostrar usuario
     */
    public function show(User $user)
    {
        $user->load(['patient', 'medicalStaff', 'roles']);
        
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => $user
        ]);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Restaurar usuario
     */
    public function restore($id)
    {
        try {
            // Buscar usuario eliminado (soft delete)
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();
            
            return response()->json([
                'success' => true,
                'message' => 'User restored successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring user: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar permanentemente
     */
    public function forceDelete($id)
    {
        try {
            // Buscar usuario (incluyendo eliminados)
            $user = User::withTrashed()->findOrFail($id);
            $user->forceDelete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted permanently'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user permanently: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener usuarios eliminados
     */
    public function trashed(IndexUserRequest $request)
    {
        try {
            $query = User::onlyTrashed()->with(['patient', 'medicalStaff', 'roles']);

            // Aplicar filtros
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('role')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Ordenar
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginar
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving deleted users: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear administrador
     */
    public function storeAdmin(StoreAdminRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Crear usuario
            $userData = $request->only(['name', 'email', 'password']);
            $userData['password'] = Hash::make($userData['password']);
            
            $user = User::create($userData);
            
            // Asignar rol de admin
            $user->assignRole('admin');
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Administrador creado exitosamente created successfully',
                'data' => $user->load(['roles'])
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating administrator: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear paciente
     */
    public function storePatient(StorePatientRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Crear usuario
            $userData = $request->only(['name', 'email', 'password']);
            $userData['password'] = Hash::make($userData['password']);
            
            $user = User::create($userData);
            
            // Asignar rol de paciente
            $user->assignRole('patient');
            
            // Crear datos de paciente
            $patientData = $request->only([
                'birth_date', 'gender', 'phone', 'address', 'blood_type',
                'allergies', 'current_medications', 'insurance_number',
                'emergency_contact_name', 'emergency_contact_phone'
            ]);
            $patientData['user_id'] = $user->id;
            
            $user->patient()->create($patientData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Patient created successfully',
                'data' => $user->load(['patient', 'roles'])
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating patient: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear doctor
     */
    public function storeDoctor(StoreDoctorRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Crear usuario
            $userData = $request->only(['name', 'email', 'password']);
            $userData['password'] = Hash::make($userData['password']);
            
            $user = User::create($userData);
            
            // Asignar rol de doctor
            $user->assignRole('doctor');
            
            // Crear datos de personal médico
            $medicalStaffData = $request->only([
                'professional_license', 'specialty_id', 'subspecialty',
                'active', 'appointment_duration', 'work_schedule'
            ]);
            $medicalStaffData['user_id'] = $user->id;
            
            $user->medicalStaff()->create($medicalStaffData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Doctor created successfully',
                'data' => $user->load(['medicalStaff', 'roles'])
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating doctor: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar administrador
     */
    public function updateAdmin(UpdateAdminRequest $request, User $user)
    {
        DB::beginTransaction();
        
        try {
            $userData = $request->only(['name', 'email']);
            
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            
            $user->update($userData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Administrador actualizado exitosamente',
                'data' => $user->load(['roles'])
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el administrador: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar paciente
     */
    public function updatePatient(UpdatePatientRequest $request, User $user)
    {
        DB::beginTransaction();
        
        try {
            $userData = $request->only(['name', 'email']);
            
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            
            $user->update($userData);
            
            // Actualizar datos del paciente
            if ($user->patient) {
                $patientData = $request->only([
                    'birth_date', 'gender', 'phone', 'address', 'blood_type',
                    'allergies', 'current_medications', 'insurance_number',
                    'emergency_contact_name', 'emergency_contact_phone'
                ]);
                
                $user->patient->update(array_filter($patientData, function($value) {
                    return $value !== null;
                }));
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Paciente actualizado exitosamente',
                'data' => $user->load(['patient', 'roles'])
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el paciente: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar doctor
     */
    public function updateDoctor(UpdateDoctorRequest $request, User $user)
    {
        DB::beginTransaction();
        
        try {
            $userData = $request->only(['name', 'email']);
            
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            
            $user->update($userData);
            
            // Actualizar datos del personal médico
            if ($user->medicalStaff) {
                $medicalData = $request->only([
                    'professional_license', 'specialty_id', 'subspecialty',
                    'active', 'appointment_duration', 'work_schedule'
                ]);
                
                $user->medicalStaff->update(array_filter($medicalData, function($value) {
                    return $value !== null;
                }));
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Doctor actualizado exitosamente',
                'data' => $user->load(['medicalStaff', 'roles'])
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el doctor: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar perfil básico del usuario autenticado
     */
    public function updateOwnProfile(UpdateUserRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $user = auth()->user();
            $userData = $request->only(['name', 'email']);
            
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            
            $user->update($userData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'data' => $user->load(['patient', 'medicalStaff', 'roles'])
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Completar perfil de paciente para el usuario autenticado
     */
    public function completeOwnPatientProfile(UpdatePatientRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $user = auth()->user();
            
            // Verificar que el usuario tenga rol de paciente
            if (!$user->hasRole('patient')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los pacientes pueden completar este perfil'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Crear datos del paciente
            $patientData = $request->only([
                'birth_date', 'gender', 'phone', 'address', 'blood_type',
                'allergies', 'current_medications', 'insurance_number',
                'emergency_contact_name', 'emergency_contact_phone'
            ]);
            
            if ($user->patient) {
                $user->patient->update($patientData);
            } else {
                $user->patient()->create($patientData);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Perfil de paciente completado exitosamente',
                'data' => $user->load(['patient', 'roles'])
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al completar el perfil de paciente: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar datos específicos de paciente para el usuario autenticado
     */
    public function updateOwnPatientData(UpdatePatientRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $user = auth()->user();
            
            if (!$user->hasRole('patient') || !$user->patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró perfil de paciente'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $patientData = $request->only([
                'birth_date', 'gender', 'phone', 'address', 'blood_type',
                'allergies', 'current_medications', 'insurance_number',
                'emergency_contact_name', 'emergency_contact_phone'
            ]);
            
            $user->patient->update(array_filter($patientData, function($value) {
                return $value !== null;
            }));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Datos de paciente actualizados exitosamente',
                'data' => $user->load(['patient', 'roles'])
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los datos de paciente: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
