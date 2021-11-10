<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{Rooms, Booking, Prices};
use DB;

/**
 * Класс Dates необходим для работы с получением дат относительно существующих цен в базе.
 * @version 1.0
 */
class Dates extends Controller
{
    /**
     * Метод getAvailable для получения списка свободных дат для бронирования
     * @param Request $request
     * @return json
     */
    public function getAvailable(Request $request)
    {

        //Ищем максимальную квоту на все номера
        //TODO: как быть если в определенный день номеров меньше?
        $maxQuotaRoomPerDay = Rooms::maxQuota();
        if(!$maxQuotaRoomPerDay) {
            return response()
                ->json(['message' => __('api.rooms.multi_not_found')])
                ->setStatusCode(404);
        }
        //Находим список дней и количество занятых номеров по дням
        $subJoin = Prices::select('dates.date', DB::raw('sum(booking.quota) as exist_quota'))
            ->fromSub(function($query){
                $query->select('date')
                    ->from('prices')
                    ->groupBy('date');
            }, 'dates')->crossJoin('booking')
                ->whereRaw('date >= checkin and date < checkout')
                ->groupBy('dates.date');

        //Проверяем превышен ли показатель занятости относительно дня, если да, исключаем из выборки
        $dates = Prices::leftJoinSub($subJoin, 't', function($join){
            $join->on('prices.date', '=', 't.date');
        })->select('prices.date')
            ->where('t.exist_quota', '<', $maxQuotaRoomPerDay)->orWhereNull('t.exist_quota')
            ->groupBy('prices.date')->get()->pluck('date');

        return response()->json($dates);
    }

}
