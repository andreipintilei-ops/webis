<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Carbon\CarbonImmutable;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quote request from the public form.
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $company
 * @property int|null $service_page_id
 * @property string|null $service_label
 * @property string|null $budget
 * @property string|null $message
 * @property string|null $source_url
 * @property string|null $referrer
 * @property array<string, string>|null $utm
 * @property string|null $gclid
 * @property CarbonImmutable|null $consent_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property LeadStatus $status
 * @property string|null $notes
 * @property int|null $assigned_to
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Page|null $servicePage
 * @property-read User|null $assignee
 */
#[Fillable([
    'name', 'email', 'phone', 'company', 'service_page_id', 'service_label',
    'budget', 'message', 'source_url', 'referrer', 'utm', 'gclid', 'consent_at',
    'ip_address', 'user_agent', 'status', 'notes', 'assigned_to',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'utm' => 'array',
            'consent_at' => 'datetime',
            'status' => LeadStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function servicePage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'service_page_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
