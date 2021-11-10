<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Rooms, Booking as ListBooking, Prices};
use DB;

/**
 * Класс Booking необходим для работы с заявками по бронированию.
 * @version 1.0
 */
class Booking extends Controller
{
    /**
     * Метод store для бронирования номера
     * @param Request $request
     * @return json
     */
    public function store(Request $request)
    {
        /**
         * Вспомогательная функция проверки корректности даты
         * @param String $date входные данные
         * @param String $format вариант преобразования
         * @return Boolean $result true или false
         */
        function validateDate($date, $format = 'Y-m-d'){
            $result = \DateTime::createFromFormat($format, $date);
            return $result && $result->format($format) == $date;
        }

        //проверка, отправили ли нам json
        if(!$request->isJson()){
            return response()
                ->json(['message' => __('api.method.only_json_allowed')])
                ->setStatusCode(404);
        }

        //получаем все необходимые данные
        $checkin = $request->input('checkin');
        $checkout = $request->input('checkout');
        $adults = $request->input('adults', 1);
        $children = $request->input('children', 0);
        $room_type = (int)$request->input('room_type');
        $quota = $request->input('rooms', 1);
        $first_name = $request->input('first_name', '');
        $last_name = $request->input('last_name', '');
        $phone = $request->input('phone', '');
        $email = $request->input('email', '');

        
        //проверка на даты
        if(!validateDate($checkin) || !preg_match('/[2][0]\d{2}-[0-1]{1}\d{1}-[0-3]\d{1}/m', $checkin)) {
            return response()
                ->json(['message' => __('api.dates.wrong_arrival')])
                ->setStatusCode(404);
        }
        if(!validateDate($checkout) || !preg_match('/[2][0]\d{2}-[0-1]{1}\d{1}-[0-3]\d{1}/m', $checkout)) {
            return response()
                ->json(['message' => __('api.dates.wrong_departure')])
                ->setStatusCode(404);
        }
        if(strtotime($checkout) <= strtotime($checkin)) {
            return response()
                ->json(['message' => __('api.dates.arrival_less_departure')])
                ->setStatusCode(404);
        }

        //проверка на людей
        if(!preg_match('/\d{1,2}/m', $adults) || $adults < 1){
            return response()
                ->json(['message' => __('api.people.wrong_adults')])
                ->setStatusCode(404);
        }
        if(!preg_match('/\d{1,2}/m', $children) || $children < 0){
            return response()
                ->json(['message' => __('api.people.wrong_children')])
                ->setStatusCode(404);
        }

        
        if (!preg_match("#[0-9a-z_\-\.]+@[0-9a-z\-\.]+\.[a-z]{2,6}$#i", $email)){
            return response()
                ->json(['message' => __('api.booking.wrong_email')])
                ->setStatusCode(404);
        }
        if (!preg_match("/((8|\+7)[\- ]?)?(\(?\d{3,4}\)?[\- ]?)?[\d\- ]{6,10}$/", $phone)){
            return response()
                ->json(['message' => __('api.booking.wrong_phone')])
                ->setStatusCode(404);
        }

        if($first_name == '' || $last_name == ''){
            return response()
                ->json(['message' => __('api.booking.wrong_name')])
                ->setStatusCode(404);
        }


        //проверка на существование комнаты
        $room = Rooms::find($room_type);
        if(!$room) {
            return response()
                ->json(['message' => __('api.rooms.one_not_found')])
                ->setStatusCode(404);
        }
        if(!preg_match('/\d{1,2}/m', $quota) || $quota < 1){
            return response()
                ->json(['message' => __('api.rooms.wrong_quota')])
                ->setStatusCode(404);
        }
        if($quota > $room->quota){
            return response()
                ->json(['message' => __('api.rooms.quota_limit_exceeded')])
                ->setStatusCode(404);
        }

        //Проверка на наличие возможности брони
        //Находим список дней и количество занятых номеров по дням
        $subJoin = Prices::select('dates.date', DB::raw('sum(booking.quota) as exist_quota'))
            ->fromSub(function($query){
                $query->select('date')
                    ->from('prices')
                    ->groupBy('date');
            }, 'dates')->crossJoin('booking')
                ->where('booking.room_id', $room->id)
                ->whereRaw('date >= checkin and date <= checkout')
                ->groupBy('booking.room_id', 'dates.date');

        //Проверяем не превышен ли показатель занятости относительно дня
        $dates = Prices::leftJoinSub($subJoin, 't', function($join){
            $join->on('prices.date', '=', 't.date');
        })->select('prices.date')
            ->where('prices.date', '>=', $checkin)
            ->where('prices.date', '<', $checkout)
            ->where('t.exist_quota', '>', $room->quota - $quota)
            ->groupBy('prices.date')->get();
        
        if(!$dates->isEmpty()){
            return response()
                ->json(['message' => __('api.rooms.all_occupied')])
                ->setStatusCode(404);
        }

        //Сохраняем заявку
        $newBooking = new ListBooking;

        $newBooking->checkin = $checkin;
        $newBooking->checkout = $checkout;
        $newBooking->adults = $adults;
        $newBooking->children = $children;
        $newBooking->room_id = $room->id;
        $newBooking->quota = $quota;
        $newBooking->first_name = $first_name;
        $newBooking->last_name = $last_name;
        $newBooking->email = $email;
        $newBooking->phone = $phone;
        $newBooking->save();

        return response()->json(['reservation_id' => $newBooking->id]);
    }
}
