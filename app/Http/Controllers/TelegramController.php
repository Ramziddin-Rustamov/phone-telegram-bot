<?php

namespace App\Http\Controllers;

use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Http\Request;
use App\Models\Contact;

class TelegramBotController extends Controller
{
    public function handle(Request $request)
    {
        $update = Telegram::commandsHandler(true);
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $text = $message->getText();

        if ($text == '/start') {
            $this->sendWelcomeMessage($chatId);
        } elseif ($text == 'Qo\'shgan raqamlarim') {
            $this->listContacts($chatId);
        } elseif ($text == 'Yana raqam qo\'shish') {
            $this->requestContactDetails($chatId);
        } else {
            $this->handleContactDetails($chatId, $text);
        }
    }

    protected function sendWelcomeMessage($chatId)
    {
        $keyboard = Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton(['text' => 'Yana raqam qo\'shish', 'callback_data' => 'add']),
                Keyboard::inlineButton(['text' => 'Qo\'shgan raqamlarim', 'callback_data' => 'list'])
            );

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => 'Men raqamlarni saqlaydigan botman. Mendan foydalanish uchun /startni bosing.',
            'reply_markup' => $keyboard
        ]);
    }

    protected function requestContactDetails($chatId)
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => 'Iltimos, Raqam egasini ismini kiriting:'
        ]);
    }

    protected function handleContactDetails($chatId, $text)
    {
        // Session orqali foydalanuvchi uchun vaqtinchalik ma'lumotlarni saqlaymiz
        $step = session()->get("user_{$chatId}_step", 0);

        switch ($step) {
            case 0:
                // Birinchi qadam: Ismni so'rash
                session()->put("user_{$chatId}_first_name", $text);
                session()->put("user_{$chatId}_step", 1);
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Familiyasini  kiriting:'
                ]);
                break;

            case 1:
                // Ikkinchi qadam: Familiyani saqlash va telefon raqamini so'rash
                session()->put("user_{$chatId}_last_name", $text);
                session()->put("user_{$chatId}_step", 2);
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Telefon raqamini kiriting:'
                ]);
                break;

            case 2:
                // Uchinchi qadam: Telefon raqamini saqlash va bazaga yozish
                $firstName = session()->get("user_{$chatId}_first_name");
                $lastName = session()->get("user_{$chatId}_last_name");
                $phone = $text;

                // Contact modelini chaqirib, ma'lumotlarni saqlash
                Contact::create([
                    'user_id' => $chatId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                ]);

                // Foydalanuvchiga xabar yuborish
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Raqamingiz muvaffaqiyatli qo\'shildi!',
                ]);

                // Session'ni tozalash va dastlabki holatga qaytarish
                session()->forget("user_{$chatId}_step");
                session()->forget("user_{$chatId}_first_name");
                session()->forget("user_{$chatId}_last_name");

                // Keyingi harakatlar uchun tugmalar ko'rsatish
                $keyboard = Keyboard::make()
                    ->inline()
                    ->row(
                        Keyboard::inlineButton(['text' => 'Yana raqam qo\'shish', 'callback_data' => 'add']),
                        Keyboard::inlineButton(['text' => 'Qo\'shgan raqamlarim', 'callback_data' => 'list'])
                    );

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Yana nima qilishni xohlaysiz?',
                    'reply_markup' => $keyboard
                ]);
                break;

            default:
                // Default holat: foydalanuvchi bilan muloqotni boshlash
                session()->put("user_{$chatId}_step", 0);
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Ismingizni kiriting:'
                ]);
                break;
        }
    }


    protected function listContacts($chatId)
    {
        $contacts = Contact::where('user_id', $chatId)->get();
        $text = "Siz qo'shgan raqamlar:\n\n";

        foreach ($contacts as $contact) {
            $text .= "Ism: {$contact->first_name} {$contact->last_name}\n";
            $text .= "Telefon raqam: {$contact->phone}\n\n";
        }

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
