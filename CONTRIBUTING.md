# Source code structures
## General
- The name of all files and directories MUST use the snake_case.
- The name of all files and directories MAY be plural OR singular.

## Directory `gen`
- All world generators and populators of a specific scene MUST lay in the same namespace.
- Multiple scenes CANNOT have the same namespace.
- The namespace MUST start with `Endermanbugzjfc\Backrooms\`.
- The namespace SHOULD end with the scene name in PascalCase.
- Other related classes, interfaces, traits, enums SHOULD lay in the same file of the world generators and populators.
- One file that uses the namespace MUST lay in `gen`.
- More than one files that use the namespace MUST lay in a subdirectory in `gen`.
- The subdirectory SHOULD be named after the scene name. [(Remind: use snake_case)](#general)
- THe subdirectory SHOULD NOT contain any other subdirectory.
- All namespaces in `gen` MUST be independent.
- All files in `gen` MUST only import items of the PocketMine-MP API AND items of other Composer libraries.

## Directory `gameplay`
- All files in `gameplay` MUST have the EXACT namespace `Endermanbugzjfc\Backrooms`.

# Scene customisability
- World generators MAY have constants and static methods as an customisation interface.
- "Gameplay flag" constants have direct control to parts of code in which its TargetGen attribute points to the world generator that belongs the constant.
- The name of "gameplay flag" constants MUST start with `GAMEPLAY_`.
- The static methods MUST have their signatures well documented.
- World generators MUST include the class-string of its populators as "populator" constants.
- The name of "populator" constants MUST start with `POPULATOR_`.
- The name of "populator" constants SHOULD end with the populator class name in SCREAMING_SNAKE_CASE.
