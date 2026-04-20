<?php

namespace App\Services\Payment;

use App\DTOs\Payment\PaymentInitiationResult;
use App\Enums\PaymentProvider;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Payment\Gateways\FatoraGateway;
use App\Services\Payment\Gateways\MtnCashGateway;
use App\Services\Payment\Gateways\SamaPayGateway;
use App\Services\Payment\Gateways\SyriatelCashGateway;
use InvalidArgumentException;

class PaymentInitiationService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private MtnCashGateway $mtnGateway,
        private SyriatelCashGateway $syriatelGateway,
        private FatoraGateway $fatoraGateway,
        private SamaPayGateway $samaPayGateway,
        private WalletService $walletService,
    ) {}

    public function initiate(int $bookingId, string $provider, int $userId, ?string $phoneNumber = null): PaymentInitiationResult
    {
        $booking = $this->bookingRepo->findOrFail($bookingId);

        $providerEnum = PaymentProvider::from($provider);

        return match ($providerEnum) {
            PaymentProvider::MtnCash      => $this->mtnGateway->initiate($booking, $phoneNumber ?? ''),
            PaymentProvider::SyriatelCash => $this->syriatelGateway->initiate($booking, $phoneNumber ?? ''),
            PaymentProvider::Fatora       => $this->fatoraGateway->initiate($booking),
            PaymentProvider::SamaPay      => $this->samaPayGateway->initiate($booking),
            PaymentProvider::Wallet       => $this->initiateWallet($booking, $userId),
            default                       => throw new InvalidArgumentException("Unsupported payment provider: {$provider}"),
        };
    }

    private function initiateWallet(Booking $booking, int $userId): PaymentInitiationResult
    {
        $result = $this->walletService->debit(
            userId: $userId,
            amount: $booking->total_price,
            description: "Booking #{$booking->booking_code}",
            referenceType: 'booking',
            referenceId: $booking->id,
        );

        return new PaymentInitiationResult(
            payment: $result->transaction,
            flowType: \App\Enums\PaymentFlowType::Internal,
            redirectUrl: null,
            otpChallengeUuid: null,
        );
    }
}
