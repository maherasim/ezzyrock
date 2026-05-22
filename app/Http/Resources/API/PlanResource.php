<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    protected $planLimitLabels = [
        'service' => 'service',
        'handyman' => 'handyman',
        'featured_service' => 'featured_service',
        'ecommerce' => 'ecommerce',
        'featured_ecommerce' => 'featured_ecommerce',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $planLimitation = optional($this->planlimit)->plan_limitation ?? [];
        $limitations = $this->formattedLimitations($planLimitation);

        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'identifier'        => $this->identifier,
            'amount'            => $this->amount,
            'duration'          => $this->duration,
            'description'       => $this->description,
            'plan_type'         => $this->plan_type,
            'type'              => $this->type,
            'module'            => $this->module,
            'trial_period'      => $this->trial_period,
            'playstore_identifier'      => $this->playstore_identifier,
            'appstore_identifier'      => $this->appstore_identifier,
            'plan_limitation'   => $planLimitation,
            'limitations'       => array_values($limitations),
            'ecommerce_limitation' => $limitations['ecommerce'],
            'featured_ecommerce_limitation' => $limitations['featured_ecommerce'],
        ];
    }

    protected function formattedLimitations($planLimitation): array
    {
        if (! is_array($planLimitation)) {
            $planLimitation = [];
        }

        $limitations = [];
        foreach ($this->planLimitLabels as $key => $labelKey) {
            $row = $planLimitation[$key] ?? [];
            $isChecked = $this->isChecked($row['is_checked'] ?? null);
            $limit = $row['limit'] ?? null;
            $numericLimit = is_numeric($limit) ? (int) $limit : null;

            $limitations[$key] = [
                'key' => $key,
                'name' => __('messages.' . $labelKey),
                'is_checked' => $isChecked ? 'on' : 'off',
                'enabled' => $isChecked,
                'limit' => $numericLimit,
                'limit_text' => $isChecked
                    ? ($numericLimit === null ? 'Unlimited' : (string) $numericLimit)
                    : null,
                'is_unlimited' => $isChecked && $numericLimit === null,
            ];
        }

        return $limitations;
    }

    protected function isChecked($value): bool
    {
        return in_array($value, ['on', 1, '1', true], true);
    }
}
