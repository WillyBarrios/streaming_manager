<?php

namespace App\Filament\Resources\PerfilResource\Pages;

use App\Filament\Resources\PerfilResource;
use App\Models\Perfil; // Importante
use Filament\Actions;
use Filament\Actions\Action; // Importante
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification; // Importante

class EditPerfil extends EditRecord
{
    protected static string $resource = PerfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // AQUÍ ESTÁ LA MAGIA
    protected function getFormActions(): array
    {
        return [
            // 1. Botón Guardar Normal
            $this->getSaveFormAction(),

            // 2. Nuestro Botón Personalizado "Guardar y Siguiente"
            Action::make('saveAndNext')
                ->label('Guardar y Editar Siguiente')
                ->color('gray') // Color gris para diferenciarlo
                ->action(function () {
                    // Primero guardamos los cambios del actual
                    $this->save();

                    // Buscamos el siguiente perfil que pertenezca a la MISMA cuenta
                    $siguientePerfil = Perfil::where('cuenta_id', $this->record->cuenta_id)
                        ->where('id', '>', $this->record->id) // Que el ID sea mayor
                        ->orderBy('id', 'asc') // El más próximo
                        ->first();

                    if ($siguientePerfil) {
                        // Si existe, redirigimos a su página de edición
                        return redirect()->to(PerfilResource::getUrl('edit', ['record' => $siguientePerfil->id]));
                    }

                    // Si no hay más, avisamos y vamos a la lista
                    Notification::make()
                        ->title('¡Listo! Has editado todos los perfiles de esta cuenta.')
                        ->success()
                        ->send();
                    
                    return redirect()->to(PerfilResource::getUrl('index'));
                }),

            // 3. Botón Cancelar
            $this->getCancelFormAction(),
        ];
    }
}