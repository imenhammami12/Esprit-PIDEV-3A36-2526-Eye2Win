<?php

namespace App\Controller\Community;

use Zxing\QrReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class QrJoinController extends AbstractController
{
    #[Route('/community/join-by-qr', name: 'community_join_by_qr', methods: ['GET','POST'])]
    public function join(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('qr_image');

            if ($file) {
                $reader = new QrReader($file->getPathname());
                //$text = (string) $reader->text(); // decoded text
//                $text = $reader->text();
//                $this->addFlash('info', 'Decoded: ' . var_export($text, true));

                $reader = new QrReader($file->getPathname());
                $text = $reader->text();

                if (!$text) {
                    $this->addFlash('error', 'QR could not be read. Try another image or zoom/crop the QR.');
                    return $this->redirectToRoute('community_join_by_qr');
                }

                $text = trim((string)$text);

// ✅ Case A: our payload format
                if (str_starts_with($text, 'EYE2WIN_INVITE:')) {
                    $token = trim(substr($text, strlen('EYE2WIN_INVITE:')));
                    if ($token !== '') {
                        return $this->redirectToRoute('community_invite_open', ['token' => $token]);
                    }
                }

// ✅ Case B: URL format (old QR)
                if (preg_match('~/community/invites/([^/]+)/open~', $text, $m)) {
                    $token = $m[1];
                    return $this->redirectToRoute('community_invite_open', ['token' => $token]);
                }

                $this->addFlash('error', 'Invalid QR code content.');
                return $this->redirectToRoute('community_join_by_qr');
//
//                // Expect "EYE2WIN_INVITE:<token>"
//                if (str_starts_with($text, 'EYE2WIN_INVITE:')) {
//                    $token = trim(substr($text, strlen('EYE2WIN_INVITE:')));
//                    if ($token !== '') {
//                        return $this->redirectToRoute('community_invite_open', ['token' => $token]);
//                    }
//                }
//
//                $this->addFlash('error', 'Invalid QR code.');
            } else {
                $this->addFlash('error', 'Please upload a QR image.');
            }
        }

        return $this->render('community/invite/join_by_qr.html.twig');
    }
}