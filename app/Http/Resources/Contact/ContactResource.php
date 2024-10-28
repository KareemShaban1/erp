<?php

namespace App\Http\Resources\Contact;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    protected bool $withFullData = true;

    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;

        return $this;
    }
    /**
     * @param $request The incoming HTTP request.
     * @return array<int|string, mixed>  The transformed array representation of the LaDivision collection.
     */

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'type' => $this->type,
                    'supplier_business_name' => $this->supplier_business_name,
                    'prefix' => $this->prefix,
                    'first_name' => $this->first_name,
                    'middle_name' => $this->middle_name,
                    'last_name' => $this->last_name,
                    'email' => $this->email,
                    'contact_id' => $this->contact_id,
                    'contact_status' => $this->contact_status,
                    'tax_number' => $this->tax_number,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    'address_line_1' => $this->address_line_1,
                    'address_line_2' => $this->address_line_2,
                    'zip_code' => $this->zip_code,
                    'dob' => $this->dob,
                    'mobile' => $this->mobile,
                    'landline' => $this->landline,
                    'alternate_number' => $this->alternate_number,
                    'pay_term_number' => $this->pay_term_number,
                    'pay_term_type' => $this->pay_term_type,
                    'credit_limit' => $this->credit_limit,
                    'created_by' => $this->created_by,
                    'converted_by' => $this->converted_by,
                    'converted_on' => $this->converted_on,
                    'balance' => $this->balance,
                    'total_rp' => $this->total_rp,
                    'total_rp_used' => $this->total_rp_used,
                    'total_rp_expired' => $this->total_rp_expired,
                    'is_default' => $this->is_default,
                    'shipping_address' => $this->shipping_address,

                    'shipping_custom_field_details' => $this->shipping_custom_field_details,
                    'is_export' => $this->is_export,
                    'export_custom_field_1' => $this->export_custom_field_1,
                    'export_custom_field_2' => $this->export_custom_field_2,
                    'export_custom_field_3' => $this->export_custom_field_3,
                    'export_custom_field_4' => $this->export_custom_field_4,
                    'export_custom_field_5' => $this->export_custom_field_5,
                    'export_custom_field_6' => $this->export_custom_field_6,
                    'position' => $this->position,
                    'customer_group_id' => $this->customer_group_id,
                    'crm_source' => $this->crm_source,
                    'crm_life_stage' => $this->crm_life_stage,
                    'custom_field1' => $this->custom_field1,
                    'custom_field2' => $this->custom_field2,
                    'custom_field3' => $this->custom_field3,
                    'custom_field4' => $this->custom_field4,
                    'custom_field5' => $this->custom_field5,
                    'custom_field6' => $this->custom_field6,
                    'custom_field7' => $this->custom_field7,
                    'custom_field8' => $this->custom_field8,
                    'custom_field9' => $this->custom_field9,
                    'custom_field10' => $this->custom_field10,


                    'created_at' => $this->created_at,
                    'deleted_at' => $this->deleted_at,
                ];
            }),
        ];


    }
}
