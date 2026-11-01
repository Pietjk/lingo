# Lingo Game Guidelines

These guidelines apply to features for the real-time multiplayer Lingo game.

## Game logic

- Keep game rules and state transitions in `app/Game`.
- Controllers are transport boundaries: validate and authorize requests, then delegate to game code. Do not put game logic in controllers.
- Keep game state authoritative on the server.

## Secret word

- Never expose the secret word to a client before its round ends.
- Check every client-visible surface, including page props, API responses, and real-time events or broadcasts. Only reveal the word after the round has ended.

## Test-first development

- Before building a feature, write a Pest test that describes its expected behavior.
- Run the test and confirm it fails for the expected reason before implementing the feature.
- Include coverage for secret-word visibility whenever a change can affect client-facing game state.

## Small components

- Keep UI components and game classes small and focused on one responsibility.
- Extract focused components or classes when a unit starts handling unrelated concerns.
