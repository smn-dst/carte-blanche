<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

final class OrderTicketsPdfGenerator
{
    public function __construct(
        private Environment $twig,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * PDF autonome (images en base64) : utilisable depuis n’importe quel domaine, pas besoin d’URL publique.
     */
    public function generate(Order $order): string
    {
        $qrDir = $this->projectDir.'/public/uploads/qrcodes';
        $ticketsPdf = [];

        foreach ($order->getTickets() as $ticket) {
            $restaurant = $ticket->getRestaurant();
            $fileName = $ticket->getQrCode();
            $path = $qrDir.'/'.($fileName ?? '');
            $qrDataUri = '';
            if (\is_string($fileName) && '' !== $fileName && is_file($path)) {
                $qrDataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
            }

            $categoryLabel = 'Restaurant';
            if (null !== $restaurant && !$restaurant->getCategories()->isEmpty()) {
                $first = $restaurant->getCategories()->first();
                if ($first instanceof Category) {
                    $categoryLabel = $first->getName() ?? 'Restaurant';
                }
            }

            $auctionDateFormatted = '';
            if (null !== $restaurant?->getAuctionDate()) {
                $auctionDateFormatted = $restaurant->getAuctionDate()->format('d/m/Y');
                if (null !== $restaurant->getAuctionTime()) {
                    $auctionDateFormatted .= ' à '.$restaurant->getAuctionTime()->format('H:i');
                }
            }

            $ticketsPdf[] = [
                'restaurantName' => $restaurant?->getName() ?? '',
                'address' => $restaurant?->getAddress() ?? '',
                'categoryLabel' => $categoryLabel,
                'auctionDateFormatted' => $auctionDateFormatted,
                'auctionLocation' => $restaurant?->getAuctionLocation() ?? '',
                'qrDataUri' => $qrDataUri,
            ];
        }

        $html = $this->twig->render('pdf/order_tickets.html.twig', [
            'order' => $order,
            'ticketsPdf' => $ticketsPdf,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
