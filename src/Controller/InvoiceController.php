<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Service\CustomerProfileService;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use RuntimeException;

class InvoiceController extends AbstractController
{
    /**
     * Downloads the invoice PDF for a given payment ID.
     *
     * This action generates a PDF of the invoice and forces the browser to download it.
     * The payment ID is used to fetch the corresponding invoice from the database.
     *
     * @param int $invoiceId
     * @param EntityManagerInterface $em
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     * @throws NotFoundHttpException
     * @throws MpdfException
     * @throws RuntimeException
     */
    public function downloadInvoicePdf(int $invoiceId, EntityManagerInterface $em, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();

        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        $logger->info(
            'Attempt to download invoice PDF.',
            [
                'invoice_id' => $invoiceId
            ]
        );

        $invoice = $em->getRepository(Invoice::class)->find($invoiceId);
        if (!$invoice) {
            $logger->error(
                'Invoice not found.',
                [
                    'invoice_id' => $invoiceId
                ]
            );
            throw $this->createNotFoundException('Invoice not found');
        }

        // Check if the user has permission to download the invoice
        if (!$authChecker->isGranted('INVOICE_VIEW', $invoice)) {
            $this->addFlash('danger', 'You are not authorized to view this invoice.');
            return $this->redirectToRoute('payments_history');
        }

        try {
            $html = $this->renderView('invoice/invoice_pdf.html.twig', [
                'invoice' => $invoice
            ]);

            $mpdf = new Mpdf();
            $mpdf->WriteHTML($html);

            $invoiceNumber = $invoice->getInvoiceNumber();
            $createdAt = $invoice->getCreatedAt()->format('Y-m-d');
            $filename = 'invoice_' . $invoiceNumber . '_' . $createdAt . '.pdf';

            $logger->info(
                'Generating invoice PDF for download.',
                [
                    'filename' => $filename
                ]
            );

            $mpdf->Output($filename, 'D');

        } catch (MpdfException $e) {
            $logger->error(
                'Error generating invoice PDF with mPDF.',
                [
                    'exception' => $e,
                    'invoice_id' => $invoiceId
                ]
            );
            throw new RuntimeException('Error generating PDF. Please try again later.');
        }

        return new Response();
    }

}