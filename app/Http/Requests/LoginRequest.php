<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class LoginRequest extends FormRequest {

 public function authorize(): bool {
     return true; 
     }
 public function rules(): array { 
    return [
        'email'=>
       [
        'required',
        'string',
        'email',
        'max:255'
        ],

        'password'=>
        [
        'required'
        ,'string',
        'min:8',
        'max:255'
        ]];
     }
 public function messages(): array {
     return 
     [
        'email.required'=>'L’email est obligatoire.',
        'email.email'=>'Veuillez saisir une adresse e-mail valide.',
        'email.max'=>'L’adresse e-mail ne doit pas dépasser 255 caractères.',
        'password.required'=>'Le mot de passe est obligatoire.',
        'password.string'=>'Le mot de passe doit être une chaîne de caractères.',
        'password.min'=>'Le mot de passe doit contenir au moins 8 caractères.',
        'password.max'=>'Le mot de passe ne doit pas dépasser 255 caractères.'
        ]; 
    }
}
