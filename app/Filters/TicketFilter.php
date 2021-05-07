<?php

namespace App\Filters;

use Kyslik\LaravelFilterable\Filter;
/**
 * Фильтр тикетов
 *
 * @author  Stanislav Shelkov <narrccoozz@gmail.com>
 */
class TicketFilter extends Filter
{
    /**
     * Available Filters and their aliases.
     *
     * @return array ex: ['method-name', 'another-method' => 'alias', 'yet-another-method' => ['alias-one', 'alias-two]]
     */
    function filterMap(): array
    {
        return [
            'state' => ['state']
        ];
    }
    /**
     * Фильтр по статусу
     *
     * @param int $stateId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function state($stateId = 0)
    {
        if($stateId) {
            //time of last ticket state's creating
            $latestStates = \DB::table('ticket_ticket_state')
                ->select('ticket_id', \DB::raw('MAX(created_at) as last_state_created_at'))
                ->groupBy('ticket_id');

            //tickets with need states
            $ticketIds = \DB::table('ticket_ticket_state')
                ->joinSub($latestStates, 'latest_states', function ($join) {
                    $join->on('ticket_ticket_state.ticket_id', '=', 'latest_states.ticket_id')
                        ->on('ticket_ticket_state.created_at', '=', 'latest_states.last_state_created_at');
                })
                ->where('ticket_ticket_state.ticket_state_id', $stateId)
                ->pluck('ticket_ticket_state.ticket_id');
            return $this->builder->whereIn('id', $ticketIds);
        } else {
            return $this->builder;
        }
    }
}
