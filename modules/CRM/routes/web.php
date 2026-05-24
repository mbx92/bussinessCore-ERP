<?php

use App\Http\Controllers\ERPModuleController;
use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\CrmActivityController;
use Modules\CRM\Http\Controllers\CrmCustomerController;
use Modules\CRM\Http\Controllers\CrmLeadController;
use Modules\CRM\Http\Controllers\CrmPipelineController;

Route::middleware(['auth', 'role:admin|manajer'])->group(function () {
    Route::get('erp/crm', [ERPModuleController::class, 'crm'])
        ->middleware('module:crm')
        ->name('erp.crm');

    Route::prefix('erp/crm')
        ->name('erp.crm.')
        ->middleware('module:crm')
        ->group(function () {
            Route::get('leads', [CrmLeadController::class, 'index'])->name('leads');
            Route::post('leads', [CrmLeadController::class, 'store'])->name('leads.store');
            Route::patch('leads/{crmLead}', [CrmLeadController::class, 'update'])->name('leads.update');
            Route::delete('leads/{crmLead}', [CrmLeadController::class, 'destroy'])->name('leads.destroy');

            Route::get('customers', [CrmCustomerController::class, 'index'])->name('customers');
            Route::post('customers', [CrmCustomerController::class, 'store'])->name('customers.store');
            Route::patch('customers/{crmCustomer}', [CrmCustomerController::class, 'update'])->name('customers.update');
            Route::delete('customers/{crmCustomer}', [CrmCustomerController::class, 'destroy'])->name('customers.destroy');

            Route::get('pipelines', [CrmPipelineController::class, 'index'])->name('pipelines');
            Route::post('pipelines', [CrmPipelineController::class, 'store'])->name('pipelines.store');
            Route::patch('pipelines/{crmPipeline}', [CrmPipelineController::class, 'update'])->name('pipelines.update');
            Route::delete('pipelines/{crmPipeline}', [CrmPipelineController::class, 'destroy'])->name('pipelines.destroy');

            Route::get('activities', [CrmActivityController::class, 'index'])->name('activities');
            Route::post('activities', [CrmActivityController::class, 'store'])->name('activities.store');
            Route::patch('activities/{crmActivity}', [CrmActivityController::class, 'update'])->name('activities.update');
            Route::delete('activities/{crmActivity}', [CrmActivityController::class, 'destroy'])->name('activities.destroy');
        });
});
