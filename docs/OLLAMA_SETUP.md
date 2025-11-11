# Ollama Setup Guide for Avinash-EYE

## What is Ollama?

Ollama allows you to run large language models locally for generating detailed, comprehensive image descriptions. It's optional but provides much better descriptions than BLIP alone.

## Installation Steps

### 1. Install Ollama

**macOS / Linux:**
```bash
curl -fsSL https://ollama.com/install.sh | sh
```

**Or download from:** https://ollama.com/download

### 2. Verify Ollama is Running

After installation, Ollama should start automatically. Check with:

```bash
ollama --version
```

You should see something like: `ollama version is 0.x.x`

### 3. Pull the LLaVA Model (Recommended for Vision)

LLaVA is a vision-language model that can understand images:

```bash
ollama pull llava
```

This will download ~4.7 GB. Wait for it to complete.

### 4. Test Ollama

```bash
ollama list
```

You should see `llava` in the list.

### 5. Enable in Avinash-EYE Settings

1. Go to **Settings** (`/settings`)
2. Scroll to **"Ollama (Detailed Descriptions)"**
3. Check ✅ **"Enable Ollama"**
4. Select **"llava"** from the dropdown
5. Click **"Save Settings"**

## Available Ollama Models

### Vision Models (Can see images):
- **llava** - Best for image descriptions (Recommended)
- **llava:13b** - Higher quality, slower, more memory

### Text-only Models (Will work but can't see images):
- **llama2** - General purpose
- **mistral** - Fast and efficient  
- **mixtral** - Highest quality
- **codellama** - Technical descriptions

## How It Works

When Ollama is enabled:

1. BLIP generates a basic caption
2. Ollama receives the image + BLIP caption
3. Ollama generates a detailed 3-4 sentence description
4. Ollama extracts smart meta tags for searching
5. Results are saved to your database

## Testing Ollama

### Check if Ollama Server is Running:
```bash
curl http://localhost:11434/api/tags
```

Should return JSON with your models.

### Test LLaVA with an image:
```bash
ollama run llava "Describe this image" --image /path/to/your/image.jpg
```

## Troubleshooting

### "Ollama not installed" in settings:
The Ollama **server** needs to be running on your machine. The Python package connects to it.

**Solution:**
```bash
# Start Ollama (it should auto-start, but you can manually start it)
ollama serve
```

### "Model not found" error:
You need to pull the model first:
```bash
ollama pull llava
```

### Ollama takes too long:
- LLaVA can take 10-30 seconds per image depending on your hardware
- Consider using a smaller model or disabling Ollama for bulk uploads
- Enable it only for important photos you want detailed descriptions for

### Check Ollama Logs:
```bash
# Check if Ollama is running
ps aux | grep ollama

# Check Ollama version
ollama --version
```

## Model Sizes & Requirements

| Model | Size | RAM Needed | Speed | Image Support |
|-------|------|------------|-------|---------------|
| llava | ~4.7 GB | 8 GB | Medium | ✅ Yes |
| llava:13b | ~7.4 GB | 16 GB | Slow | ✅ Yes |
| llama2 | ~3.8 GB | 8 GB | Fast | ❌ No |
| mistral | ~4.1 GB | 8 GB | Fast | ❌ No |
| mixtral | ~26 GB | 32 GB | Slow | ❌ No |

**⚠️ Important:** For image descriptions, use **llava** or **llava:13b**. Other models are text-only.

## Recommended Workflow

### For Detailed Archives:
1. Enable Ollama
2. Use LLaVA model
3. Upload photos one at a time or small batches
4. Get comprehensive descriptions

### For Bulk Uploads:
1. Disable Ollama (faster)
2. Use BLIP only
3. Upload many photos quickly
4. Basic but good descriptions

## Benefits of Using Ollama

✅ **Detailed Descriptions**: 3-4 sentences vs 1 sentence from BLIP
✅ **Better Meta Tags**: Smarter keyword extraction
✅ **Context Awareness**: Understands scene, mood, atmosphere
✅ **100% Local**: No API calls, complete privacy
✅ **Free**: No costs, unlimited usage

## Example Comparison

### BLIP Only:
```
"A person standing in a room"
```

### With Ollama (LLaVA):
```
"A young professional standing in a modern office space with large windows. 
The room features contemporary furniture and warm lighting. The person is 
wearing business casual attire and appears to be in a meeting area with 
visible technology equipment in the background."
```

Meta tags: `professional, office, modern, business, indoor, windows, technology, workspace, contemporary`

---

## Quick Start Commands

```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull LLaVA model
ollama pull llava

# Verify
ollama list

# Test with an image
ollama run llava "What's in this image?" --image ~/Pictures/test.jpg
```

Then enable in Avinash-EYE Settings!

---

**Need Help?** Check Ollama docs: https://ollama.com/

