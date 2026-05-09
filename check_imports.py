import os
import re
from pathlib import Path

controllers_dir = Path("Modules/Parametrage/Http/Controllers")
issues = []

for controller_file in sorted(controllers_dir.glob("*.php")):
    with open(controller_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    imports = set(re.findall(r'use Modules\Parametrage\Entities\(\w+);', content))
    usages = set(re.findall(r'\b([A-Z][a-zA-Z0-9]+)::(?:all|query|where|find|create|update|get|paginate|delete|with|withTrashed|findOrFail)\s*\(', content))
    
    usages_filtered = {u for u in usages if u not in ['Request', 'Response', 'User', 'Inertia', 'Auth', 'App', 'Devises', 'Log', 'DB', 'Cache']}
    
    missing = usages_filtered - imports
    
    if missing:
        issues.append((controller_file.name, sorted(missing), sorted(imports)))

if issues:
    print("CONTROLLERS WITH MISSING ENTITY IMPORTS:\n")
    for fname, missing, imported in issues:
        print(f"{fname}")
        print(f"  Missing: {missing}")
        print(f"  Imported: {imported}")
        print()
else:
    print("All controllers OK!")
