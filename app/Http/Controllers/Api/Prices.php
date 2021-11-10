<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Rooms, Booking, Prices as ListPrices};
use DB;

/**
 * Класс Prices необходим для работы с получением информации о ценах номеров.
 * @version 1.0
 */
class Prices extends Controller
{
    /**
     * Метод getByParameters для получения цены за номер относительно передаваемых параметров
     * @param Request $request
     * @return json
     */
    public function getByParameters(Request $request)
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

        //получаем все необходимые данные
        $checkin = $request->input('checkin');
        $checkout = $request->input('checkout');
        $adults = $request->input('adults', 1);
        $children = $request->input('children', 0);
        $room_type = (int)$request->input('room_type');
        $quota = $request->input('rooms', 1);

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
        //дата приезда должна быть меньше даты отъезда
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

        //Посчитаем цену
        $price = ListPrices::where([
            ['room_id', '=', $room->id],
            ['adults', '=', $adults],
            ['children', '=', $children],
            ['date', '>=', $checkin],
            ['date', '<', $checkout]
        ]);

        //Проверим на наличие вариантов размещения по ценам
        $checkExistPrices = $price->clone()->count();

        if(!$checkExistPrices){
            return response()
                ->json(['message' => __('api.rooms.one_not_found')])
                ->setStatusCode(404);
        }

        //Проверка на наличие возможности брони
        //Находим список дней и количество занятых номеров по дням
        $subJoin = ListPrices::select('dates.date', DB::raw('sum(booking.quota) as exist_quota'))
            ->fromSub(function($query){
                $query->select('date')
                    ->from('prices')
                    ->groupBy('date');
            }, 'dates')->crossJoin('booking')
                ->where('booking.room_id', $room->id)
                ->whereRaw('date >= checkin and date <= checkout')
                ->groupBy('dates.date');

        //Проверяем не превышен ли показатель занятости относительно дня
        $dates = ListPrices::leftJoinSub($subJoin, 't', function($join){
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

        return response()->json(['total' => $price->sum('price') * $quota]);

    }
}
