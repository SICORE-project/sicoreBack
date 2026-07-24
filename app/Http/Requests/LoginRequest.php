<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class LoginRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['email'=>['required','string','email','max:255'],'password'=>['required','string','max:255']]; }
 public function messages(): array { return ['email.required'=>'L’email est obligatoire.','password.required'=>'Le mot de passe est obligatoire.']; }
}
