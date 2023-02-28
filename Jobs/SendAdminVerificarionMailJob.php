<?php

namespace Modules\Authentication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Authentication\Notifications\SendAdminVerificationMail;
use Modules\Authentication\Repositories\Interfaces\IUserRepository;
use App\Models\User;

class SendAdminVerificarionMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $user;
    private $userRepository;
    public function __construct(User $user,IUserRepository $userRepository)
    {
        $this->user = $user;
        $this->userRepository = $userRepository;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user=$this->userRepository->createCode($this->user->id);
        $code=$user->pin_code;
        $this->user->notify(new SendAdminVerificationMail($code));
    }
}
