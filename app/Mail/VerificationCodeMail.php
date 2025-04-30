<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
	use Queueable, SerializesModels;

	public $code;

	public function __construct($code)
	{
		$this->code = $code;
	}

	public function build()
	{
		return $this->subject('Email Verification Code - ' . config('app.name'))
			->markdown('emails.verification-code', [
				'code' => $this->code
			]);
	}
}
