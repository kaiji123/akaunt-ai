<?php

namespace App\Http\Middleware;

use App\Traits\Companies;
use App\Traits\Users;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;

class IdentifyCompany
{
    use Companies, Users;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->request = $request;

        Log::info('IdentifyCompany: Middleware called', [
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        $company_id = $this->getCompanyId();

        Log::info('IdentifyCompany: Company ID retrieved', ['company_id' => $company_id]);

        if (empty($company_id)) {
            Log::error('IdentifyCompany: Missing company ID - aborting');
            abort(500, 'Missing company');
        }

        // Check if user can access company
        if ($this->request->isNotSigned($company_id) && $this->isNotUserCompany($company_id)) {
            Log::warning('IdentifyCompany: User cannot access company', ['company_id' => $company_id]);
            throw new AuthenticationException('Unauthenticated.', $guards);
        }

        Log::info('IdentifyCompany: User authorized for company', ['company_id' => $company_id]);

        // Set company as current
        $company = company($company_id);

        if (empty($company)) {
            Log::error('IdentifyCompany: Company not found', ['company_id' => $company_id]);
            abort(500, 'Company not found');
        }

        Log::info('IdentifyCompany: Company found and making current', [
            'company_id' => $company_id,
            'company_name' => $company->name
        ]);

        $company->makeCurrent();

        // Fix file/folder paths
        config(['filesystems.disks.' . config('filesystems.default') . '.url' => url('/' . $company_id)  . '/uploads']);

        // Fix routes
        if ($this->request->isNotApi()) {
            app('url')->defaults(['company_id' => $company_id]);
            $this->request->route()->forgetParameter('company_id');
        }

        Log::info('IdentifyCompany: Middleware completed successfully');

        return $next($this->request);
    }
}