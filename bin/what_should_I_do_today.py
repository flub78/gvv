import random
from datetime import date

# Baseline allocations (easy to modify)
base_allocations = {
    "New Features": 35,
    "Bug Fixing": 15,
    "Refactoring": 10,
    "Test Coverage": 10,
    "DevOps / Monitoring": 10,
    "Security & Compliance": 10,
    "Documentation & Knowledge Sharing": 10
}

def adjust_allocations(allocations, pain_factors):
    adjusted = allocations.copy()

    # Adjustment rules
    if pain_factors["bugs"]:
        adjusted["Bug Fixing"] += 10
        adjusted["New Features"] -= 10

    if pain_factors["tests"]:
        adjusted["Test Coverage"] += 10
        adjusted["New Features"] -= 10

    if pain_factors["refactoring"]:
        adjusted["Refactoring"] += 10
        adjusted["New Features"] -= 10

    if pain_factors["devops"]:
        adjusted["DevOps / Monitoring"] += 10
        adjusted["Refactoring"] -= 10

    if pain_factors["security"]:
        adjusted["Security & Compliance"] += 10
        adjusted["New Features"] -= 10

    if pain_factors["docs"]:
        adjusted["Documentation & Knowledge Sharing"] += 5
        adjusted["New Features"] -= 5

    if pain_factors["business"]:
        adjusted["New Features"] += 10
        adjusted["Refactoring"] -= 10

    # Guardrails: ensure no category drops below 5%
    for k in adjusted:
        if adjusted[k] < 5:
            adjusted[k] = 5

    # Normalize back to 100
    total = sum(adjusted.values())
    adjusted = {k: round(v * 100 / total) for k, v in adjusted.items()}

    return adjusted

def pick_task(allocations):
    tasks = []
    for task, weight in allocations.items():
        tasks.extend([task] * weight)
    return random.choice(tasks)

def generate_weekly_plan(allocations, days=5):
    # Set seed based on current date to ensure consistency
    today = date.today()
    random.seed(today.toordinal())
    
    return [pick_task(allocations) for _ in range(days)]

if __name__ == "__main__":
    # Ask if user wants to adjust pain factors
    adjust_pain = input("Do you want to adjust pain factors? (Y/N) [N]: ").strip().lower() == "y"
    
    pain_factors = {
        "bugs": False,
        "tests": False,
        "refactoring": False,
        "devops": False,
        "security": False,
        "docs": False,
        "business": False
    }
    
    if adjust_pain:
        print("\nAnswer Y/N for each pain factor (default = N):")
        pain_factors = {
            "bugs": input("High bug volume? (Y/N) [N]: ").strip().lower() == "y",
            "tests": input("Low test coverage pain? (Y/N) [N]: ").strip().lower() == "y",
            "refactoring": input("High complexity blocking work? (Y/N) [N]: ").strip().lower() == "y",
            "devops": input("Deployment/monitoring painful? (Y/N) [N]: ").strip().lower() == "y",
            "security": input("Security/compliance issues urgent? (Y/N) [N]: ").strip().lower() == "y",
            "docs": input("Documentation gaps causing pain? (Y/N) [N]: ").strip().lower() == "y",
            "business": input("Business pressure for features? (Y/N) [N]: ").strip().lower() == "y"
        }

    allocations = adjust_allocations(base_allocations, pain_factors)
    print("\nAdjusted allocations:", allocations)

    # Set seed for consistent daily task selection
    today = date.today()
    random.seed(today.toordinal())

    # Daily suggestion
    today_task = pick_task(allocations)
    print("\n👉 Today you should focus on:", today_task)

    # Weekly plan (will start with same task as today)
    weekly_plan = generate_weekly_plan(allocations, days=5)
    print("\n📅 Weekly Plan:")
    for i, task in enumerate(weekly_plan, start=1):
        print(f" Day {i}: {task}")
