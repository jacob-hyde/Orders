<?php

namespace JacobHyde\Orders\App\Services;

use JacobHyde\Orders\Models\Contracts\PaymentIntentContract;

abstract class PaymentService
{
    /**
     * Amount in dollars
     *
     * @var float
     */
    private float $amount;
    
    /**
     * Fee in dollars
     *
     * @var float
     */
    private float $fee;
    /**
     * The user being charged
     *
     * @var App\Models\User
     */
    private $user;

    /**
     * The selling user
     *
     * @var App\Models\User
     */
    private $sellerUser;

    /**
     * Set the user
     *
     * @param App\Models\User $user
     * @return self
     */
    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set the seller user
     *
     * @param App\Models\User $sellerUser
     * @return self
     */
    public function setSeller($sellerUser): self
    {
        $this->sellerUser = $sellerUser;
        return $this;
    }

    /**
     * Set the amount
     *
     * @param float $amount
     * @return self
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Set the fee
     *
     * @param float $fee
     * @return self
     */
    public function setFee(float $fee): self
    {
        $this->fee = $fee;
        return $this;
    }

    abstract public function create(): PaymentIntentContract;

    abstract protected function createIntentData(): array;
}