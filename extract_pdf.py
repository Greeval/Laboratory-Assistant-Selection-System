import PyPDF2
import os
import sys

folder = "proposal dan rencana"
output_dir = "proposal_text"
os.makedirs(output_dir, exist_ok=True)

for filename in os.listdir(folder):
    if filename.endswith(".pdf"):
        filepath = os.path.join(folder, filename)
        print(f"\n{'='*60}")
        print(f"FILE: {filename}")
        print(f"{'='*60}")
        try:
            reader = PyPDF2.PdfReader(filepath)
            all_text = []
            for i, page in enumerate(reader.pages):
                text = page.extract_text()
                if text:
                    all_text.append(f"--- PAGE {i+1} ---\n{text}")
            
            full_text = "\n".join(all_text)
            
            # Save to text file
            out_file = os.path.join(output_dir, filename.replace(".pdf", ".txt"))
            with open(out_file, "w", encoding="utf-8") as f:
                f.write(full_text)
            
            print(f"Extracted {len(reader.pages)} pages -> {out_file}")
            # Print first 3000 chars as preview
            print(full_text[:3000])
            if len(full_text) > 3000:
                print(f"\n... [truncated, full text saved to {out_file}]")
        except Exception as e:
            print(f"ERROR: {e}")
