<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ChatbotController extends AbstractController
{
    #[Route('/chatbot', name: 'app_chatbot', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse([
                'error' => 'Message is required'
            ], 400);
        }

        try {
            $response = $this->getResponse($message);
            return new JsonResponse(['response' => $response]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getResponse(string $message): string
    {
        $message = strtolower(trim($message));

        // Greetings
        if ($this->containsAny($message, ['bonjour', 'salut', 'hello', 'hi', 'hey'])) {
            return 'Bonjour! Je suis votre assistant de voyage TripToGo. Comment puis-je vous aider aujourd\'hui?';
        }

        // Farewells
        if ($this->containsAny($message, ['au revoir', 'bye', 'goodbye', 'à bientôt'])) {
            return 'Au revoir! N\'hésitez pas à revenir si vous avez d\'autres questions sur vos voyages.';
        }

        // Destinations
        if ($this->containsAny($message, ['destination', 'où aller', 'voyage', 'partir'])) {
            return 'TripToGo propose des voyages vers de nombreuses destinations populaires comme la Thaïlande, Dubaï, Paris, et le Sud de la France.';
        }

        // Thailand
        if ($this->containsAny($message, ['thailande', 'thailand', 'bangkok', 'phuket'])) {
            return 'La Thaïlande est une destination magnifique avec ses plages paradisiaques, ses temples bouddhistes et sa cuisine délicieuse.';
        }

        // Dubai
        if ($this->containsAny($message, ['dubai', 'dubaï', 'emirats', 'uae'])) {
            return 'Dubaï offre un mélange unique de modernité et tradition, avec ses gratte-ciels impressionnants et ses souks traditionnels.';
        }

        // Paris
        if ($this->containsAny($message, ['paris', 'france', 'tour eiffel', 'louvre'])) {
            return 'Paris, la ville lumière, est célèbre pour sa Tour Eiffel, le Louvre, et son ambiance romantique unique.';
        }

        // South of France
        if ($this->containsAny($message, ['sud france', 'côte d\'azur', 'nice', 'cannes', 'monaco'])) {
            return 'Le Sud de la France offre de belles plages, un climat ensoleillé, et des villes charmantes comme Nice et Cannes.';
        }

        // Price related
        if ($this->containsAny($message, ['prix', 'tarif', 'coût', 'budget', 'cher', 'pas cher'])) {
            return 'Nos prix varient selon la destination et la saison. Je peux vous aider à trouver des offres adaptées à votre budget.';
        }

        // Weather
        if ($this->containsAny($message, ['météo', 'climat', 'saison', 'quand partir'])) {
            return 'La meilleure période pour voyager dépend de votre destination. Je peux vous conseiller sur les saisons idéales pour chaque pays.';
        }

        // TripToGo specific
        if ($this->containsAny($message, ['triptogo', 'trip to go', 'votre agence'])) {
            return 'TripToGo est votre partenaire de voyage de confiance, offrant des services personnalisés et des expériences mémorables.';
        }

        // Help
        if ($this->containsAny($message, ['aide', 'help', 'comment', 'que faire'])) {
            return 'Je peux vous aider à choisir une destination, vous informer sur les prix, la météo, et répondre à vos questions sur nos services.';
        }

        // Default response
        return 'Je suis votre assistant de voyage TripToGo. Je peux vous aider à choisir une destination, vous informer sur les prix, la météo, et répondre à vos questions sur nos services.';
    }

    private function containsAny(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
} 