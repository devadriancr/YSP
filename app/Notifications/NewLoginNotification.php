<?php

namespace App\Notifications;

use DateTimeZone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class NewLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string|null>  $details
     */
    public function __construct(public array $details) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->details;

        $timezone = in_array($d['timezone'], DateTimeZone::listIdentifiers(), true)
            ? $d['timezone']
            : config('app.timezone');

        $incognito = match ($d['incognito']) {
            'yes' => 'Sí (probable)',
            'no' => 'No',
            default => 'No se pudo determinar',
        };

        $rows = [
            'Fecha y hora' => Carbon::parse($d['logged_in_at'])->setTimezone($timezone)->locale('es')->translatedFormat('d \d\e F \d\e Y, H:i:s').' ('.$timezone.')',
            'ID de inicio de sesión' => $d['login_id'],
            'Navegador' => trim($d['browser'].' '.$d['browser_version']),
            'Sistema operativo' => trim($d['platform'].' '.$d['platform_version']),
            'Tipo de dispositivo' => $d['device_type'],
            'Modo incógnito' => $incognito,
            'Dirección IP' => $d['ip'] ?: 'No disponible',
            'Ubicación aproximada' => $d['location'],
            'ID de dispositivo' => $d['device_id'] ? Str::limit($d['device_id'], 12, '') : null,
        ];

        $table = "| Dato | Detalle |\n|:--|:--|\n";
        foreach (array_filter($rows) as $label => $value) {
            $table .= '| **'.$label.'** | '.str_replace('|', '/', $value)." |\n";
        }

        return (new MailMessage)
            ->subject('Nuevo inicio de sesión en tu cuenta')
            ->greeting('¡Hola, '.Str::before($notifiable->name, ' ').'!')
            ->line('Detectamos un inicio de sesión en tu cuenta con los siguientes datos:')
            ->line(new HtmlString(Str::markdown($table, ['html_input' => 'strip'])))
            ->when($d['location'], fn (MailMessage $message) => $message->line(new HtmlString('<small>La ubicación es aproximada y se calcula a partir de la dirección IP. Datos de geolocalización de <a href="https://db-ip.com">DB-IP.com</a>.</small>')))
            ->line('Si fuiste tú, no necesitas hacer nada.')
            ->line('Si no reconoces este acceso, cambia tu contraseña de inmediato.')
            ->action('Cambiar contraseña', route('profile'))
            ->salutation(new HtmlString('Saludos,<br>Portal de Proveedores'));
    }
}
